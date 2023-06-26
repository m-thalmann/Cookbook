<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Cookbook;
use App\Models\Recipe;
use Tests\TestCase;

class CookbookCategoryTest extends TestCase {
    public function testItReturnsAllCategoriesForAGivenCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user);

        $recipes = Recipe::factory(5)->create(['cookbook_id' => $cookbook->id]);

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/categories");

        $response->assertOk();

        $response->assertJsonFragment([
            'data' => $recipes
                ->pluck('category')
                ->unique()
                ->values()
                ->toArray(),
        ]);
    }

    public function testItFailsWhenTheUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/categories");

        $response->assertNotFound();
    }

    public function testItSucceedsWhenTheUserIsAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $recipes = Recipe::factory(5)->create(['cookbook_id' => $cookbook->id]);

        $response = $this->getJson("/v1/cookbooks/{$cookbook->id}/categories");

        $response->assertOk();
    }
}
