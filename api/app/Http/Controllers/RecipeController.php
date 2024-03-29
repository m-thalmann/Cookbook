<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Cookbook;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\Recipes\IndexRecipesParameters;
use App\OpenApi\Parameters\Recipes\ShowRecipesParameters;
use App\OpenApi\Parameters\Recipes\ShowSharedRecipesParameters;
use App\OpenApi\RequestBodies\Recipes\CreateRecipeRequestBody;
use App\OpenApi\RequestBodies\Recipes\UpdateRecipeRequestBody;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\Recipes\RecipeCreatedResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\Recipes\RecipeIndexResponse;
use App\OpenApi\Responses\Recipes\RecipeShowResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class RecipeController extends Controller {
    /**
     * Lists all recipes (visible to the user)
     *
     * If authentication is provided it will show all recipes owned by the user.
     * If furthermore the `all` parameter is set, all visible recipes for the user are returned (admin can see any recipe).
     *
     * If no authentication is provided all public recipes are returned (independent of the `all` parameter)
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexRecipesParameters::class)]
    #[OpenApi\Response(factory: RecipeIndexResponse::class, statusCode: 200)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        $all = $request->exists('all');
        $includeDeleted =
            authUser()?->is_admin && $request->exists('include-deleted');

        $recipes = Recipe::query()->with(['user', 'thumbnail']);

        if (auth()->check()) {
            $recipes->with([
                'cookbook' => function ($query) {
                    if (!authUser()->is_admin) {
                        $query->forUser(authUser())->exists();
                    }
                },
            ]);
        }

        $recipes->organized($request)->forUser(authUser(), $all);

        if ($includeDeleted) {
            $recipes->withTrashed();
        }

        return response()->pagination(
            fn($perPage) => RecipeResource::collection(
                $recipes->paginate($perPage)
            )
        );
    }

    /**
     * Creates a new recipe
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: CreateRecipeRequestBody::class)]
    #[OpenApi\Response(factory: RecipeCreatedResponse::class, statusCode: 201)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function store(Request $request) {
        $this->verifyNoDemo();

        $this->authorize('create', Recipe::class);

        $data = $request->validate([
            'is_public' => ['boolean'],
            'name' => ['required', 'filled', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'category' => ['nullable', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'max:2000'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:1'],
            'resting_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cooking_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cookbook_id' => [
                'bail',
                'nullable',
                Rule::exists('cookbooks', 'id')->where(function ($query) {
                    (new Cookbook())->scopeForUser(
                        $query,
                        authUser(),
                        mustBeAdmin: true
                    );
                }),
            ],
        ]);

        $recipe = DB::transaction(function () use ($data, $request) {
            $recipe = authUser()
                ->recipes()
                ->create($data);

            $ingredientsData = $request->validate([
                'ingredients' => ['array'],
            ]);

            $ingredients = Arr::get($ingredientsData, 'ingredients', []);

            if (count($ingredients) > 0) {
                foreach ($ingredients as $ingredientIndex => $ingredient) {
                    try {
                        IngredientController::storeIngredient(
                            $ingredient,
                            $recipe
                        );
                    } catch (ValidationException $e) {
                        $ingredientErrors = collect($e->validator->errors());
                        throw ValidationException::withMessages(
                            $ingredientErrors
                                ->mapWithKeys(
                                    fn($errors, $key) => [
                                        "ingredients.{$ingredientIndex}.{$key}" => $errors,
                                    ]
                                )
                                ->toArray()
                        );
                    }
                }
            }

            return $recipe;
        });

        return RecipeResource::make(
            $recipe
                ->refresh()
                ->load(['user', 'ingredients', 'cookbook'])
                ->makeVisible('share_uuid')
        )
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns the recipe with the given id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowRecipesParameters::class)]
    #[OpenApi\Response(factory: RecipeShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function show(Recipe $recipe) {
        $this->authorizeAnonymously('view', $recipe);

        $recipe->load(['user', 'ingredients', 'images']);

        if (auth()->check()) {
            // load cookbook if user is part of it or is owner

            $recipe->load([
                'cookbook' => function ($query) {
                    if (!authUser()->is_admin) {
                        $query->forUser(authUser())->exists();
                    }
                },
            ]);
        }

        if (optional(authUser())->can('update', $recipe)) {
            $recipe->makeVisible('share_uuid');
        }

        return RecipeResource::make($recipe);
    }

    /**
     * Returns the recipe with the given share-uuid
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowSharedRecipesParameters::class)]
    #[OpenApi\Response(factory: RecipeShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function showShared(string $shareUuid) {
        $recipeQuery = Recipe::query()->with(['user', 'ingredients', 'images']);

        if (auth()->check()) {
            // load cookbook if user is part of it

            $recipeQuery->with([
                'cookbook' => function ($query) {
                    if (!authUser()->is_admin) {
                        $query->forUser(authUser())->exists();
                    }
                },
            ]);
        }

        $recipe = $recipeQuery->where('share_uuid', $shareUuid)->firstOrFail();

        $recipe->makeHidden('thumbnail');

        return RecipeResource::make($recipe);
    }

    /**
     * Updates an existing recipe
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowRecipesParameters::class)]
    #[OpenApi\RequestBody(factory: UpdateRecipeRequestBody::class)]
    #[OpenApi\Response(factory: RecipeShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function update(Request $request, Recipe $recipe) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $recipe);

        $validationRules = [
            'name' => ['filled', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'category' => ['nullable', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'max:2000'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:1'],
            'resting_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cooking_time_minutes' => ['nullable', 'integer', 'min:1'],
            'is_shared' => ['boolean'],
        ];

        if (authUser()->is_admin || auth()->id() === $recipe->user_id) {
            $validationRules['user_id'] = ['exists:App\Models\User,id'];
            $validationRules['is_public'] = ['boolean'];

            $validationRules['cookbook_id'] = [
                'bail',
                'nullable',
                Rule::exists('cookbooks', 'id')->where(function ($query) {
                    if (!authUser()->is_admin) {
                        (new Cookbook())->scopeForUser(
                            $query,
                            authUser(),
                            mustBeAdmin: true
                        );
                    }
                }),
            ];
        }

        $data = $request->validate($validationRules);

        if (isset($data['is_shared'])) {
            $isShared = $data['is_shared'];

            if ($isShared) {
                if ($recipe->share_uuid === null) {
                    $recipe->share_uuid = Str::uuid();
                }
            } else {
                $recipe->share_uuid = null;
            }

            Arr::forget($data, 'is_shared');
        }

        $recipe->update($data);

        return RecipeResource::make(
            $recipe
                ->load(['user', 'ingredients', 'cookbook'])
                ->makeVisible('share_uuid')
        )->response();
    }

    /**
     * Moves the recipe to the trash
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: ShowRecipesParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(Recipe $recipe) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('delete', $recipe);

        $recipe->delete();

        return response()->noContent();
    }
}
