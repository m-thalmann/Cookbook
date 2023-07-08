<?php

namespace Tests\Feature\Api;

use App\Models\Recipe;
use Tests\TestCase;

class RecipeCategoryTest extends TestCase {
    public function testItReturnsAllCategoriesForAUsersRecipes() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipes = Recipe::factory(5)->create(['user_id' => $user->id]);

        $otherRecipes = Recipe::factory(5)->create([
            'category' => 'this-category-is-not-included',
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();

        $categories = $recipes
            ->sortBy('id')
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();

        $response->assertJson([
            'data' => $categories,
        ]);
    }

    public function testItReturnsAllCategoriesForRecipesTheUserCanSee() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipes = Recipe::factory(5)->create(['user_id' => $user->id]);

        $publicRecipes = Recipe::factory(5)->create([
            'is_public' => true,
        ]);

        $otherRecipes = Recipe::factory(5)->create([
            'category' => 'this-category-is-not-included',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/categories?all');

        $response->assertOk();

        $categories = $recipes
            ->merge($publicRecipes)
            ->sortBy('id')
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();

        $response->assertJson([
            'data' => $categories,
        ]);
    }

    public function testItReturnsAllCategoriesForAllRecipesIfIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $publicRecipes = Recipe::factory(5)->create([
            'is_public' => true,
        ]);

        $privateRecipes = Recipe::factory(5)->create([
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/categories?all');

        $response->assertOk();

        $categories = $publicRecipes
            ->merge($privateRecipes)
            ->sortBy('id')
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();

        $response->assertJson([
            'data' => $categories,
        ]);
    }
}
