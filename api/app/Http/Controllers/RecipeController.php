<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Cookbook;
use App\OpenApi\Parameters\Recipes\IndexRecipesParameters;
use App\OpenApi\Parameters\Recipes\IndexRecipesTrashParameters;
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

        $recipes = Recipe::query()->with(['user', 'thumbnail']);

        if (auth()->check()) {
            $recipes->with([
                'cookbook' => function ($query) {
                    $query->forUser(authUser())->exists();
                },
            ]);
        }

        $recipes->organized($request)->forUser(authUser(), $all);

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
        $this->authorize('create', Recipe::class);

        $data = $request->validate([
            'is_public' => ['boolean'],
            'name' => ['required', 'filled', 'max:255'],
            'description' => ['nullable', 'filled', 'max:255'],
            'category' => ['nullable', 'filled', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'filled', 'max:2000'],
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
                foreach ($ingredients as $ingredient) {
                    IngredientController::storeIngredient($ingredient, $recipe);
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
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
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
                    $query->forUser(authUser())->exists();
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
     *
     * @param string $shareUuid The recipe's share-uuid
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
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
                    $query->forUser(authUser())->exists();
                },
            ]);
        }

        $recipe = $recipeQuery->where('share_uuid', $shareUuid)->firstOrFail();

        $recipe->makeHidden('thumbnail');

        return RecipeResource::make($recipe);
    }

    /**
     * Updates an existing recipe
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
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
        $this->authorizeAnonymously('update', $recipe);

        $validationRules = [
            'user_id' => ['exists:App\Models\User,id'],
            'is_public' => ['boolean'],
            'name' => ['filled', 'max:255'],
            'description' => ['nullable', 'filled', 'max:255'],
            'category' => ['nullable', 'filled', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'filled', 'max:2000'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:1'],
            'resting_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cooking_time_minutes' => ['nullable', 'integer', 'min:1'],
            'is_shared' => ['boolean'],
        ];

        if (auth()->id() === $recipe->user_id) {
            // if the user is the owner it can update the cookbook

            $validationRules['cookbook_id'] = [
                'bail',
                'nullable',
                Rule::exists('cookbooks', 'id')->where(function ($query) {
                    (new Cookbook())->scopeForUser(
                        $query,
                        authUser(),
                        mustBeAdmin: true
                    );
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

        return RecipeResource::make($recipe->makeVisible('share_uuid'));
    }

    /**
     * Moves the recipe to the trash
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
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
        $this->authorizeAnonymously('delete', $recipe);

        $recipe->delete();

        return response()->noContent();
    }

    /**
     * Lists all recipes in the authenticated user's trash
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexRecipesTrashParameters::class)]
    #[OpenApi\Response(factory: RecipeIndexResponse::class, statusCode: 200)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function indexTrash(Request $request) {
        $recipes = authUser()
            ->recipes()
            ->with([
                'user',
                'thumbnail',
                'cookbook' => function ($query) {
                    $query->forUser(authUser())->exists();
                },
            ])
            ->onlyTrashed()
            ->organized($request);

        return response()->pagination(
            fn($perPage) => RecipeResource::collection(
                $recipes->paginate($perPage)
            )
        );
    }

    /**
     * Deletes all recipes in the trash for the authenticated user
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function truncateTrash() {
        $query = authUser()
            ->recipes()
            ->onlyTrashed();

        Recipe::deleteImageFiles($query);

        $query->forceDelete();

        return response()->noContent();
    }

    /**
     * Deletes the recipe
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function forceDestroy(Recipe $recipe) {
        $this->authorizeAnonymously('forceDelete', $recipe);

        $recipe->forceDelete();

        return response()->noContent();
    }

    /**
     * Restores the recipe from the trash
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipes'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function restore(Recipe $recipe) {
        $this->authorizeAnonymously('restore', $recipe);

        $recipe->restore();

        return response()->noContent();
    }
}
