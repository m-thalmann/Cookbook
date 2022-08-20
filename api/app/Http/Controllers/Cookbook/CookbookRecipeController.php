<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Cookbook;
use Illuminate\Http\Request;

class CookbookRecipeController extends Controller {
    public function index(Request $request, Cookbook $cookbook) {
        $this->authorizeAnonymously('view', $cookbook);

        $recipes = $cookbook
            ->recipes()
            ->with(['user', 'cookbook', 'thumbnail'])
            ->organized($request);

        return response()->pagination(
            RecipeResource::collection($recipes->paginate())
        );
    }
}

