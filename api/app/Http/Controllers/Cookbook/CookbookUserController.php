<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Cookbook;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CookbookUserController extends Controller {
    public function index(Cookbook $cookbook) {
        $this->authorizeAnonymously('update', $cookbook);

        return response()->pagination($cookbook->users()->paginate());
    }

    public function store(Request $request, Cookbook $cookbook) {
        $this->authorizeAnonymously('update', $cookbook);

        $data = $request->validate([
            'user_id' => [
                'bail',
                'required',
                'exists:App\Models\User,id',
                Rule::unique('cookbook_user', 'user_id')->where(
                    fn($query) => $query->where('cookbook_id', $cookbook->id)
                ),
            ],
            'is_admin' => ['required', 'boolean'],
        ]);

        $cookbook->users()->attach($data['user_id'], [
            'is_admin' => $data['is_admin'],
        ]);

        $cookbook->load('users');

        return JsonResource::make($cookbook)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Cookbook $cookbook, User $user) {
        $this->authorizeAnonymously('update', $cookbook);

        if ($user->user_id === auth()->id()) {
            throw new AuthorizationException(__('messages.cant_update_self'));
        }

        $data = $request->validate([
            'is_admin' => ['boolean'],
        ]);

        $user->update($data);

        $cookbook->load('users');

        return JsonResource::make($cookbook);
    }

    public function destroy(Cookbook $cookbook, int $user) {
        $this->authorizeAnonymously('update', $cookbook);

        if (
            $cookbook
                ->users()
                ->whereNot('users.id', $user)
                ->where('cookbook_user.is_admin', true)
                ->count() === 0
        ) {
            throw new ConflictHttpException(
                __('messages.cookbooks.cant_delete_last_admin_user')
            );
        }

        $cookbook->users()->detach($user);

        Recipe::query()
            ->where('user_id', $user)
            ->where('cookbook_id', $cookbook->id)
            ->update(['cookbook_id' => null]);

        return response()->noContent();
    }
}

