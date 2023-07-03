<?php

namespace Tests\Feature\Api;

use App\Models\Cookbook;
use App\Models\Recipe;
use App\Models\User;
use Tests\TestCase;

class AdminDashboardTest extends TestCase {
    public function testItReturnsTheExpectedData() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $users = User::factory(5)->create();
        $recipes = Recipe::factory(5)->create();
        $cookbooks = Cookbook::factory(5)->create();

        $response = $this->getJson('/v1/admin/dashboard');

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'api' => ['version', 'environment'],
                'users' => ['admin_amount', 'total_amount'],
                'recipes' => [
                    'public_amount',
                    'private_amount',
                    'total_amount',
                ],
                'cookbooks' => ['total_amount'],
                'recipe_images' => ['total_amount', 'storage_size'],
            ],
        ]);

        $this->assertEquals(
            User::count(),
            $response->json('data.users.total_amount')
        );
        $this->assertEquals(
            count($recipes),
            $response->json('data.recipes.total_amount')
        );
        $this->assertEquals(
            count($cookbooks),
            $response->json('data.cookbooks.total_amount')
        );
    }

    public function testItFailsIfUserIsNotAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson('/v1/admin/dashboard');

        $response->assertForbidden();
    }
}
