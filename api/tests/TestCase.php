<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use TokenAuth\TokenAuth;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;

    protected const DEFAULT_USER_PASSWORD = 'lA-8pass';

    /**
     * Creates a test-user
     *
     * @param string $password The password to set for the user
     *
     * @return User
     */
    protected static function createUser(
        $password = self::DEFAULT_USER_PASSWORD
    ) {
        return User::factory()->create([
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Creates a test user and sets it as the authenticated one
     * using the `Sanctum::actingAs` function
     *
     * @param string $password The password to set for the user
     *
     * @return User
     */
    protected static function createAndLoginUser(
        $password = self::DEFAULT_USER_PASSWORD
    ) {
        $user = self::createUser($password);

        TokenAuth::actingAs($user);

        return $user;
    }

    /**
     * Asserts that the received pagination response has the correct format
     * and the correct amount of elements.
     *
     * @param array $dataStructure
     * @param int $expectedAmount
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected static function assertJsonPagination(
        $dataStructure,
        $expectedAmount,
        $response
    ) {
        $response->assertJsonStructure([
            'data' => $expectedAmount === 0 ? [] : [$dataStructure],
            'meta' => [
                'count',
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);

        $response->assertJsonPath('meta.count', $expectedAmount);
    }
}
