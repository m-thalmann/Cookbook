<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientController extends Controller {
    public function list(Request $request) {
        $request->validate([
            'search' => ['required', 'min:2'],
        ]);

        return Ingredient::query()
            ->search($request)
            ->whereHas('recipe', function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            })
            ->distinct()
            ->get(['name', 'unit']);
    }

    public function store(Request $request, Recipe $recipe) {
        $this->authorizeAnonymously('update', $recipe);

        $data = $request->validate([
            'name' => ['required', 'filled', 'max:40'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'filled', 'max:20'],
            'group' => ['nullable', 'filled', 'max:20'],
        ]);

        $ingredient = Ingredient::make($data);
        $ingredient->recipe_id = $recipe->id;

        $ingredient->save();

        return JsonResource::make($ingredient)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Ingredient $ingredient) {
        $this->authorize('update', $ingredient);

        $data = $request->validate([
            'name' => ['filled', 'max:40'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'filled', 'max:20'],
            'group' => ['nullable', 'filled', 'max:20'],
        ]);

        $ingredient->update($data);

        return JsonResource::make($ingredient);
    }

    public function destroy(Ingredient $ingredient) {
        $this->authorize('delete', $ingredient);

        $ingredient->delete();

        return response()->noContent();
    }
}

