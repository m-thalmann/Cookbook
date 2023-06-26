<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Cookbook;
use App\Models\Recipe;
use Tests\TestCase;

class CookbookRecipeTest extends TestCase {
    public function testItReturnsAllRecipesForAGivenCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user);

        $recipes = Recipe::factory(5)->create(['cookbook_id' => $cookbook->id]);

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/recipes");

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'user_id',
                'cookbook_id',
                'is_public',
                'name',
                'description',
                'category',
                'portions',
                'difficulty',
                'preparation',
                'preparation_time_minutes',
                'resting_time_minutes',
                'cooking_time_minutes',
                'deleted_at',
                'created_at',
                'updated_at',
                'user' => ['id', 'name', 'email', 'language_code'],
                'user_can_edit',
                // thumbnail is excluded because it will not exist
                'cookbook' => ['id', 'name', 'created_at', 'updated_at'],
            ],
            count($recipes),
            $response
        );
    }

    public function testItFailsWhenTheUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/recipes");

        $response->assertNotFound();
    }

    public function testItSucceedsWhenTheUserIsAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $recipes = Recipe::factory(5)->create(['cookbook_id' => $cookbook->id]);

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/recipes");

        $response->assertOk();

        $this->assertEquals(count($recipes), $response->json('meta.total'));
    }
}
