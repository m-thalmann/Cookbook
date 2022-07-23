<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecipeController extends Controller {
    public function index(Request $request) {
        $all = $request->exists('all');

        $recipes = Recipe::query()
            ->with(['user', 'thumbnail'])
            ->organized($request)
            ->forUser(authUser(), $all);

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

        $recipe
            ->load(['user', 'ingredients', 'images'])
            ->makeHidden('thumbnail');

        if (authUser()->can('update', $recipe)) {
            $recipe->makeVisible('share_uuid');
        }

        return RecipeResource::make($recipe);
    }

    public function showShared(string $recipeShareUuid) {
        $recipe = Recipe::query()
            ->with(['user', 'ingredients', 'images'])
            ->where('share_uuid', $recipeShareUuid)
            ->firstOrFail();

        $recipe->makeHidden('thumbnail');

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
            'shared' => ['boolean'],
        ]);

        if (isset($data['shared'])) {
            $shared = $data['shared'];

            if ($shared) {
                if ($recipe->share_uuid === null) {
                    $recipe->share_uuid = Str::uuid();
                }
            } else {
                $recipe->share_uuid = null;
            }

            Arr::forget($data, 'shared');
        }

        $recipe->update($data);

        return RecipeResource::make($recipe->makeVisible('share_uuid'));
    }

    public function destroy(Recipe $recipe) {
        $this->authorizeAnonymously('delete', $recipe);

        $recipe->delete();

        return response()->noContent();
    }

    public function indexTrash(Request $request) {
        $recipes = authUser()
            ->recipes()
            ->with('user')
            ->onlyTrashed()
            ->organized($request);

        return response()->pagination(
            RecipeResource::collection($recipes->paginate())
        );
    }

    public function truncateTrash() {
        $query = authUser()
            ->recipes()
            ->onlyTrashed();

        Recipe::deleteImageFiles($query->clone());

        $query->forceDelete();

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

