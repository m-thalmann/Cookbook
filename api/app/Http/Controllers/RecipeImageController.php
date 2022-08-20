<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecipeImageController extends Controller {
    public function index(Recipe $recipe) {
        $this->authorize('view', $recipe);

        return JsonResource::collection($recipe->images);
    }

    public function store(Request $request, Recipe $recipe) {
        $this->authorize('update', $recipe);

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

    public function destroy(RecipeImage $recipeImage) {
        $this->authorize('update', $recipeImage->recipe);

        $recipeImage->delete();

        return response()->noContent();
    }
}

