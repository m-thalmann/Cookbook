<?php

namespace App\Http\Controllers;

use App\Exceptions\UnauthorizedHttpException;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\OpenApi\Parameters\Users\IndexUsersParameters;
use App\OpenApi\RequestBodies\Users\CreateUserRequestBody;
use App\OpenApi\RequestBodies\Users\UpdateUserRequestBody;
use App\OpenApi\Responses\ConflictResponse;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\Users\UserCreatedResponse;
use App\OpenApi\Responses\Users\UserIndexResponse;
use App\OpenApi\Responses\Users\UserShowDetailedResponse;
use App\OpenApi\Responses\Users\UserShowResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class UserController extends Controller {
    /**
     * Lists all users
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Parameters(factory: IndexUsersParameters::class)]
    #[OpenApi\Response(factory: UserIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        $this->authorize('viewAny', User::class);

        return response()->pagination(
            fn($perPage) => UserResource::collection(
                User::query()
                    ->organized($request)
                    ->paginate($perPage)
            )
        );
    }

    /**
     * Creates a new user
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\RequestBody(factory: CreateUserRequestBody::class)]
    #[OpenApi\Response(factory: UserCreatedResponse::class, statusCode: 201)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function store(Request $request) {
        $this->verifyNoDemo();

        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'filled', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
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
            $user->markEmailAsVerified(); // saves after marking as verified
        } else {
            $user->save();
            $user->sendEmailVerificationNotification();
        }

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns the user with the given id
     *
     * @param User $user The user's id
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[
        OpenApi\Response(
            factory: UserShowDetailedResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function show(User $user) {
        $this->authorizeAnonymously('view', $user);

        return UserResource::make($user);
    }

    /**
     * Returns the user with the given email
     *
     * @param string $email The email of the searched user
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Response(factory: UserShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function showByEmail(string $email) {
        return User::query()
            ->where('email', $email)
            ->firstOrFail();
    }

    /**
     * Updates an existing user
     *
     * @param User $user The user's id
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\RequestBody(factory: UpdateUserRequestBody::class)]
    #[
        OpenApi\Response(
            factory: UserShowDetailedResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function update(Request $request, User $user) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $user);

        $data = $request->validate([
            'name' => ['filled', 'string', 'max:255'],
            'email' => [
                'bail',
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

        if (Arr::hasAny($data, ['password']) || $user->id !== auth()->id()) {
            if (!authUser()->hasVerifiedEmail()) {
                throw UnauthorizedHttpException::unverified(
                    __('messages.email_must_be_verified')
                );
            }
        }

        // has to confirm current password if updating self credentials
        if (
            $user->id === auth()->id() &&
            Arr::hasAny($data, ['email', 'password'])
        ) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ]);
        }

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

    /**
     * Deletes the user
     *
     * @param User $user The user's id
     */
    #[OpenApi\Operation(tags: ['Users'], security: 'AccessTokenSecurityScheme')]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[OpenApi\Response(factory: ConflictResponse::class, statusCode: 409)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(User $user) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('delete', $user);

        // check if user is last admin user in at least one cookbook
        $userDeleteRestrictedByCookbooks = $user
            ->cookbooks()
            ->whereDoesntHave('users', function ($query) {
                $query
                    ->whereNot('user_id', auth()->id())
                    ->where('cookbook_user.is_admin', true);
            })
            ->exists();

        if ($userDeleteRestrictedByCookbooks) {
            throw new ConflictHttpException(
                __('messages.cookbooks.is_last_admin_user_in_some')
            );
        }

        $user->delete();

        return response()->noContent();
    }
}
