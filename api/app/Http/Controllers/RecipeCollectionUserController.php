<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeCollection;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;

class RecipeCollectionUserController extends Controller {
    public function index(RecipeCollection $collection) {
        $this->authorizeAnonymously('update', $collection);

        return response()->pagination($collection->users()->paginate());
    }

    public function store(Request $request, RecipeCollection $collection) {
        $this->authorizeAnonymously('update', $collection);

        $data = $request->validate([
            'user_id' => [
                'bail',
                'required',
                'exists:App\Models\User,id',
                Rule::unique('recipe_collection_user', 'user_id')->where(
                    fn($query) => $query->where(
                        'recipe_collection_id',
                        $collection->id
                    )
                ),
            ],
            'is_admin' => ['required', 'boolean'],
        ]);

        $collection->users()->attach($data['user_id'], [
            'is_admin' => $data['is_admin'],
        ]);

        $collection->load('users');

        return JsonResource::make($collection)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        Request $request,
        RecipeCollection $collection,
        User $user
    ) {
        $this->authorizeAnonymously('update', $collection);

        if ($user->user_id === auth()->id()) {
            throw new AuthorizationException(__('messages.cant_update_self'));
        }

        $data = $request->validate([
            'is_admin' => ['boolean'],
        ]);

        $user->update($data);

        $collection->load('users');

        return JsonResource::make($collection);
    }

    public function destroy(RecipeCollection $collection, int $user) {
        $this->authorizeAnonymously('update', $collection);

        $collection->users()->detach($user);

        Recipe::query()
            ->where('user_id', $user)
            ->where('recipe_collection_id', $collection->id)
            ->update(['recipe_collection_id' => null]);

        return response()->noContent();
    }
}

