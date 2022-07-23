<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeCategoryController extends Controller {
    public function index(Request $request) {
        $all = $request->exists('all');

        $categories = Recipe::query()
            ->forUser(authUser(), $all)
            ->orderBy('category', 'asc')
            ->get('category')
            ->pluck('category');

        return JsonResource::make($categories);
    }
}

