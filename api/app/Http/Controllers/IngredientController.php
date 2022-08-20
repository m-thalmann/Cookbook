<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IngredientController extends Controller {
    public function index(Request $request) {
        return Ingredient::query()
            ->search($request)
            ->whereHas('recipe', function ($query) {
                $query->whereRelation('user', 'user_id', auth()->id());
            })
            ->select(['name', 'unit'])
            ->distinct()
            ->get();
    }

    public function store(Request $request, Recipe $recipe) {
        $this->authorizeAnonymously('update', $recipe);

        $ingredient = self::storeIngredient($request->all(), $recipe);

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

