<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\OpenApi\Parameters\Ingredients\IndexIngredientsParameters;
use App\OpenApi\RequestBodies\Ingredients\CreateIngredientRequestBody;
use App\OpenApi\RequestBodies\Ingredients\UpdateIngredientRequestBody;
use App\OpenApi\Responses\Ingredients\IngredientCreatedResponse;
use App\OpenApi\Responses\Ingredients\IngredientIndexResponse;
use App\OpenApi\Responses\Ingredients\IngredientShowResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class IngredientController extends Controller {
    /**
     * Lists all distinct ingredients (name and unit) for the authenticated user
     */
    #[
        OpenApi\Operation(
            tags: ['Ingredients'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexIngredientsParameters::class)]
    #[
        OpenApi\Response(
            factory: IngredientIndexResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        return JsonResource::make(
            Ingredient::query()
                ->search($request)
                ->sort($request)
                ->whereHas('recipe', function ($query) {
                    $query->whereRelation('user', 'user_id', auth()->id());
                })
                ->select(['name', 'unit'])
                ->distinct()
                ->get()
        );
    }

    /**
     * Creates a new ingredient for the given recipe
     *
     * @param Recipe $recipe The id of the recipe
     */
    #[
        OpenApi\Operation(
            tags: ['Ingredients'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\RequestBody(factory: CreateIngredientRequestBody::class)]
    #[
        OpenApi\Response(
            factory: IngredientCreatedResponse::class,
            statusCode: 201
        )
    ]
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
    public function store(Request $request, Recipe $recipe) {
        $this->authorizeAnonymously('update', $recipe);

        $ingredient = self::storeIngredient($request->all(), $recipe);

        return JsonResource::make($ingredient)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Updates an existing ingredient
     *
     * @param Ingredient $ingredient The id of the ingredient
     */
    #[
        OpenApi\Operation(
            tags: ['Ingredients'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\RequestBody(factory: UpdateIngredientRequestBody::class)]
    #[OpenApi\Response(factory: IngredientShowResponse::class, statusCode: 200)]
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
    public function update(Request $request, Ingredient $ingredient) {
        $this->authorizeAnonymously('update', $ingredient);

        $data = $request->validate([
            'name' => ['filled', 'max:40'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'filled', 'max:20'],
            'group' => ['nullable', 'filled', 'max:20'],
        ]);

        $ingredient->update($data);

        return JsonResource::make($ingredient);
    }

    /**
     * Deletes the ingredient
     *
     * @param Ingredient $ingredient The ingredient's id
     */
    #[
        OpenApi\Operation(
            tags: ['Ingredients'],
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
    public function destroy(Ingredient $ingredient) {
        $this->authorizeAnonymously('delete', $ingredient);

        $ingredient->delete();

        return response()->noContent();
    }

    /**
     * Validates and stores the given data for the ingredient
     *
     * @param array $ingredientData
     * @param Recipe $recipe
     *
     * @return Ingredient
     */
    public static function storeIngredient(
        array $ingredientData,
        Recipe $recipe
    ) {
        $data = Validator::make($ingredientData, [
            'name' => ['required', 'filled', 'max:40'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'filled', 'max:20'],
            'group' => ['nullable', 'filled', 'max:20'],
        ])->validate();

        if (
            !Ingredient::isUniqueInRecipe(
                $recipe,
                $data['name'],
                $data['group'] ?? null
            )
        ) {
            throw ValidationException::withMessages([
                'name' => __('validation.ingredient_not_unique'),
            ]);
        }

        return $recipe->ingredients()->create($data);
    }
}

