<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\RecipeCollection;
use Illuminate\Http\Request;

class RecipeCollectionRecipeController extends Controller {
    public function index(Request $request, RecipeCollection $collection) {
        $this->authorizeAnonymously('view', $collection);

        $recipes = $collection
            ->recipes()
            ->with(['user', 'recipeCollection', 'thumbnail'])
            ->organized($request);

        return response()->pagination(
            RecipeResource::collection($recipes->paginate())
        );
    }
}

