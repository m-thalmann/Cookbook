<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller {
    public function index(Request $request) {
        $user = authUser();

        $isLoggedIn = $user !== null;
        $all = $request->exists('all');
        $isAdminUser = (bool) $user?->is_admin;

        $recipes = Recipe::query()
            ->with('user')
            ->organized($request);

        $recipes->where(function ($query) use (
            $isLoggedIn,
            $all,
            $isAdminUser
        ) {
            if ($isLoggedIn && (!$all || !$isAdminUser)) {
                $query->where('user_id', auth()->id());
            }

            if (!$isLoggedIn || ($all && !$isAdminUser)) {
                $query->orWhere('is_public', true);
            }
        });

        return response()->pagination(
            RecipeResource::collection($recipes->paginate())
        );
    }

    public function store(Request $request) {
        $this->authorize('create', Recipe::class);

        $data = $request->validate([
            'is_public' => ['boolean'],
            'language_code' => ['required', 'min:2', 'max:2'],
            'name' => ['required', 'filled', 'max:255'],
            'description' => ['nullable', 'filled', 'max:255'],
            'category' => ['nullable', 'filled', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'filled', 'max:2000'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:1'],
            'resting_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cooking_time_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['user_id'] = auth()->id();

        $recipe = Recipe::create($data);

        return RecipeResource::make($recipe)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Recipe $recipe) {
        $this->authorizeAnonymously('view', $recipe);

        $recipe->load(['user', 'ingredients']);

        return RecipeResource::make($recipe);
    }

    public function update(Request $request, Recipe $recipe) {
        $this->authorizeAnonymously('update', $recipe);

        $data = $request->validate([
            'user_id' => ['exists:App\Models\User,id'],
            'is_public' => ['boolean'],
            'language_code' => ['filled', 'min:2', 'max:2'],
            'name' => ['filled', 'max:255'],
            'description' => ['nullable', 'filled', 'max:255'],
            'category' => ['nullable', 'filled', 'max:50'],
            'portions' => ['nullable', 'integer', 'min:1'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'preparation' => ['nullable', 'filled', 'max:2000'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:1'],
            'resting_time_minutes' => ['nullable', 'integer', 'min:1'],
            'cooking_time_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $recipe->update($data);

        return RecipeResource::make($recipe);
    }

    public function destroy(Recipe $recipe) {
        $this->authorizeAnonymously('delete', $recipe);

        $recipe->delete();

        return response()->noContent();
    }

    public function forceDestroy(Recipe $recipe) {
        $this->authorizeAnonymously('forceDelete', $recipe);

        $recipe->forceDelete();

        return response()->noContent();
    }

    public function restore(Recipe $recipe) {
        $this->authorizeAnonymously('restore', $recipe);

        $recipe->restore();

        return response()->noContent();
    }
}

