<?php

namespace App\Http\Controllers;

use App\Models\RecipeCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeCollectionController extends Controller {
    public function index(Request $request) {
        $all = $request->exists('all');

        $collections = RecipeCollection::query()->withCount([
            'recipes',
            'users',
        ]);

        if (!$all || !authUser()->is_admin) {
            $collections->whereRelation('users', 'user_id', auth()->id());
        }

        return response()->pagination($collections->paginate());
    }

    public function store(Request $request) {
        $this->authorize('create', RecipeCollection::class);

        $data = $request->validate([
            'name' => ['required', 'filled', 'max:100'],
        ]);

        /**
         * @var RecipeCollection
         */
        $collection = RecipeCollection::create($data);
        $collection->users()->attach(auth()->id(), [
            'is_admin' => true,
        ]);

        return JsonResource::make($collection)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, RecipeCollection $collection) {
        $this->authorizeAnonymously('update', $collection);

        $data = $request->validate([
            'name' => ['filled', 'max:100'],
        ]);

        $collection->update($data);

        return JsonResource::make($collection);
    }

    public function destroy(RecipeCollection $collection) {
        $this->authorizeAnonymously('delete', $collection);

        $collection->delete();

        return response()->noContent();
    }
}

