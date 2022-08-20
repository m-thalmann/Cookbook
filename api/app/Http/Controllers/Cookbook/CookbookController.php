<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Cookbook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CookbookController extends Controller {
    public function index(Request $request) {
        $all = $request->exists('all');

        $cookbooks = Cookbook::query()
            ->withCount(['recipes', 'users'])
            ->sort($request)
            ->search($request);

        if (!$all || !authUser()->is_admin) {
            $cookbooks->forUser(authUser());
        }

        return response()->pagination($cookbooks->paginate());
    }

    public function store(Request $request) {
        $this->authorize('create', Cookbook::class);

        $data = $request->validate([
            'name' => ['required', 'filled', 'max:100'],
        ]);

        /**
         * @var Cookbook
         */
        $cookbook = Cookbook::create($data);
        $cookbook->users()->attach(auth()->id(), [
            'is_admin' => true,
        ]);

        return JsonResource::make($cookbook)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Cookbook $cookbook) {
        $this->authorizeAnonymously('update', $cookbook);

        $data = $request->validate([
            'name' => ['filled', 'max:100'],
        ]);

        $cookbook->update($data);

        return JsonResource::make($cookbook);
    }

    public function destroy(Cookbook $cookbook) {
        $this->authorizeAnonymously('delete', $cookbook);

        $cookbook->delete();

        return response()->noContent();
    }
}

