<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Recipe;
use App\Models\RecipeImage;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\RequestBodies\RecipeImages\CreateRecipeImageRequestBody;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\RecipeImages\RecipeImageCreatedResponse;
use App\OpenApi\Responses\RecipeImages\RecipeImageIndexResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class RecipeImageController extends Controller {
    /**
     * Lists all images for the given recipe
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Images'],
            security: 'OptionalAccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[
        OpenApi\Response(
            factory: RecipeImageIndexResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Recipe $recipe) {
        $this->authorizeAnonymously('view', $recipe);

        return JsonResource::collection($recipe->images);
    }

    /**
     * Creates a new image for the given recipe
     *
     * @param Recipe $recipe The recipe's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Images'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: CreateRecipeImageRequestBody::class)]
    #[
        OpenApi\Response(
            factory: RecipeImageCreatedResponse::class,
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
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $recipe);

        $request->validate([
            'image' => ['required', 'image'],
        ]);

        $imagePath = $request
            ->file('image')
            ->store(RecipeImage::IMAGE_DIRECTORY, 'public');

        if ($imagePath === false) {
            throw new HttpException(500);
        }

        $image = $recipe->images()->make();
        $image->image_path = $imagePath;
        $image->save();

        return JsonResource::make($image)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete the given recipe image
     *
     * @param RecipeImage $recipeImage The recipe-image's id
     */
    #[
        OpenApi\Operation(
            tags: ['Recipe-Images'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(RecipeImage $recipeImage) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $recipeImage->recipe);

        $recipeImage->delete();

        return response()->noContent();
    }
}
