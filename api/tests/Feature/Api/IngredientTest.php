<?php

namespace Tests\Feature\Api;

use App\Models\Cookbook;
use App\Models\Ingredient;
use App\Models\Recipe;
use Tests\TestCase;

class IngredientTest extends TestCase {
    public function testIndexReturnsAllDistinctIngredientsForTheUser() {
        $user = $this->createAndLoginUser(isAdmin: true); // does not matter whether user is admin

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredients = Ingredient::factory(10)->create([
            'recipe_id' => $recipe->id,
        ]);

        // not included
        $otherRecipe = Recipe::factory()->create();
        $otherIngredients = Ingredient::factory(10)->create([
            'recipe_id' => $otherRecipe->id,
        ]);

        $response = $this->getJson('/v1/ingredients');

        $response->assertOk();

        $uniqueIngredients = $ingredients->unique(
            fn($item) => "{$item->name}.{$item->unit}"
        );

        $response->assertJsonCount(count($uniqueIngredients), 'data');

        $response->assertJsonStructure([
            'data' => [['name', 'unit']],
        ]);
    }

    public function testStoreCreatesANewIngredient() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->make();

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'order_index' => 0,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('ingredients', [
            'name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'recipe_id' => $recipe->id,
        ]);
    }

    public function testStoreFailsIfIngredientIsNotUniqueForRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'order_index' => 0,
        ]);

        $response->assertUnprocessable();
    }

    public function testStoreSucceedsIfIngredientIsUniqueInDifferentGroupsForRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
            'group' => 'group1',
        ]);

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'group' => 'group2',
            'order_index' => 0,
        ]);

        $response->assertCreated();
    }

    public function testStoreFailsIfUserIsNotAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => false]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->make();

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'order_index' => 0,
        ]);

        $response->assertNotFound();
    }

    public function testStoreSucceedsIfUserIsAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => true]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->make();

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'order_index' => 0,
        ]);

        $response->assertCreated();
    }

    public function testStoreSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();

        $ingredient = Ingredient::factory()->make();

        $response = $this->postJson("/v1/recipes/{$recipe->id}/ingredients", [
            'name' => $ingredient->name,
            'amount' => $ingredient->amount,
            'unit' => $ingredient->unit,
            'order_index' => 0,
        ]);

        $response->assertCreated();
    }

    public function testUpdateUpdatesTheIngredient() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $newIngredient = Ingredient::factory()->make();

        $response = $this->putJson("/v1/ingredients/{$ingredient->id}", [
            'name' => $newIngredient->name,
            'amount' => $newIngredient->amount,
            'unit' => $newIngredient->unit,
            'order_index' => 0,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'name' => $newIngredient->name,
            'amount' => $newIngredient->amount,
            'unit' => $newIngredient->unit,
        ]);
    }

    public function testUpdateFailsIfIngredientIsNotUniqueForRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $otherIngredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->putJson("/v1/ingredients/{$ingredient->id}", [
            'name' => $otherIngredient->name,
            'amount' => $otherIngredient->amount,
            'unit' => $otherIngredient->unit,
            'order_index' => 0,
        ]);

        $response->assertUnprocessable();
    }

    public function testUpdateFailsIfUserIsNotAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => false]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $newIngredient = Ingredient::factory()->make();

        $response = $this->putJson("/v1/ingredients/{$ingredient->id}", [
            'name' => $newIngredient->name,
            'amount' => $newIngredient->amount,
            'unit' => $newIngredient->unit,
            'order_index' => 0,
        ]);

        $response->assertNotFound();
    }

    public function testUpdateSucceedsIfUserIsAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => true]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $newIngredient = Ingredient::factory()->make();

        $response = $this->putJson("/v1/ingredients/{$ingredient->id}", [
            'name' => $newIngredient->name,
            'amount' => $newIngredient->amount,
            'unit' => $newIngredient->unit,
            'order_index' => 0,
        ]);

        $response->assertOk();
    }

    public function testUpdateSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $newIngredient = Ingredient::factory()->make();

        $response = $this->putJson("/v1/ingredients/{$ingredient->id}", [
            'name' => $newIngredient->name,
            'amount' => $newIngredient->amount,
            'unit' => $newIngredient->unit,
            'order_index' => 0,
        ]);

        $response->assertOk();
    }

    public function testDestroyRemovesAnIngredient() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->deleteJson("/v1/ingredients/{$ingredient->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('ingredients', [
            'id' => $ingredient->id,
        ]);
    }

    public function testDestroyFailsIfUserIsNotAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => false]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->deleteJson("/v1/ingredients/{$ingredient->id}");

        $response->assertNotFound();
    }

    public function testDestroySucceedsIfUserIsAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => true]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->deleteJson("/v1/ingredients/{$ingredient->id}");

        $response->assertNoContent();
    }

    public function testDestroySucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();

        $ingredient = Ingredient::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $response = $this->deleteJson("/v1/ingredients/{$ingredient->id}");

        $response->assertNoContent();
    }
}
