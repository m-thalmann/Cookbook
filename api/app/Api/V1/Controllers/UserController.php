<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller {
    public function index(Request $request) {
        $this->authorize('viewAny', User::class);

        return response()->pagination(
            UserResource::collection(
                User::query()
                    ->organized($request)
                    ->paginate()
            )
        );
    }

    public function store(Request $request) {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'first_name' => ['required', 'filled', 'string', 'max:255'],
            'last_name' => ['required', 'filled', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::default()],
            'language_code' => ['nullable', 'min:2', 'max:2'],
            'is_admin' => ['boolean'],
            'is_verified' => ['boolean'],
        ]);

        $isAdmin = Arr::pull($data, 'is_admin') ?? false;
        $isVerified = Arr::pull($data, 'is_verified') ?? false;

        $data['password'] = Hash::make($data['password']);

        /**
         * @var User
         */
        $user = User::make($data);

        $user->is_admin = $isAdmin;

        if ($isVerified) {
            $user->email_verified_at = now();
        } else {
            $user->sendEmailVerificationNotification();
        }

        $user->save();

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user) {
        $this->authorizeAnonymously('show', $user);

        return UserResource::make($user);
    }

    public function update(Request $request, User $user) {
        $this->authorizeAnonymously('update', $user);

        $data = $request->validate([
            'first_name' => ['filled', 'string', 'max:255'],
            'last_name' => ['filled', 'string', 'max:255'],
            'email' => [
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => [Password::default()],
            'language_code' => ['nullable', 'min:2', 'max:2'],
            'is_admin' => ['boolean'],
            'is_verified' => ['boolean'],
            'do_logout' => ['boolean'],
        ]);

        $isAdmin = Arr::pull($data, 'is_admin');
        $isVerified = Arr::pull($data, 'is_verified');

        $doLogout = Arr::pull($data, 'do_logout') ?? false;

        if ($isAdmin !== null) {
            $this->authorize('admin', $user);

            $user->is_admin = $isAdmin;
        }
        if ($isVerified !== null) {
            $this->authorize('admin', $user);

            if ($user->hasVerifiedEmail() && !$isVerified) {
                $user->email_verified_at = null;
            } elseif (!$user->hasVerifiedEmail() && $isVerified) {
                $user->email_verified_at = now();
            }
        }

        if (Arr::exists($data, 'password')) {
            // dont set the password again if it is the same
            if (!Hash::check($data['password'], $user->password)) {
                $data['password'] = Hash::make($data['password']);
            } else {
                Arr::forget($data, 'password');
            }
        }

        $user->fill($data);

        $doLogout =
            $doLogout || $user->isDirty(['email', 'password', 'is_admin']); // logout if credentials or/and role have been changed

        if ($user->isDirty('email') && $isVerified === null) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($doLogout) {
            $user->tokens()->delete();
        }

        return UserResource::make($user);
    }

    public function destroy(User $user) {
        $this->authorizeAnonymously('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}

