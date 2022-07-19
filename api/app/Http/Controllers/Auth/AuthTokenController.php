<?php

namespace App\Http\Controllers\Auth;

use App\Http\Resources\AuthTokenResource;
use App\Http\Controllers\Controller;
use App\Models\AuthToken;

class AuthTokenController extends Controller {
    public function index() {
        return response()->pagination(
            AuthTokenResource::collection(
                authUser()
                    ->tokens()
                    ->active()
                    ->accessTokens()
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
