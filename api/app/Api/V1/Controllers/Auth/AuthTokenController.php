<?php

namespace App\Api\V1\Controllers\Auth;

use App\Api\V1\Resources\AuthTokenResource;
use App\Http\Controllers\Controller;
use App\Models\AuthToken;
use TokenAuth\TokenAuth;

class AuthTokenController extends Controller {
    public function index() {
        return response()->pagination(
            AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->where('expires_at', '>', now())
                    ->whereNull('revoked_at')
                    ->where('type', TokenAuth::TYPE_ACCESS)
                    ->paginate()
            )
        );
    }

    public function show(AuthToken $authToken) {
        $this->authorizeAnonymously('view', $authToken);

        return AuthTokenResource::make($authToken);
    }

    public function indexGroup(int $groupId) {
        return response()->pagination(
            AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->where('group_id', $groupId)
                    ->paginate()
            )
        );
    }

    public function destroy(AuthToken $authToken) {
        $this->authorizeAnonymously('delete', $authToken);

        $authToken->deleteAllTokensFromSameGroup();

        return response()->noContent();
    }

    public function truncate() {
        authUser()
            ->tokens()
            ->delete();

        return response()->noContent();
    }
}

