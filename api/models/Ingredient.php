<?php

namespace API\Models;

use API\Inc\Validation;
use PAF\Model\Model;

/**
 * @tablename ingredients
 */
class Ingredient extends Model {
    /**
     * @prop
     * @primary
     * @autoincrement
     * @var integer
     */
    public $id;

    /**
     * @prop
     * @var integer
     * @editable false
     */
    public $recipeId;

    // TODO: minExclusive not working
    /**
     * @prop
     * @minExclusive 0
     * @var float|null
     */
    public $amount;

    /**
     * @prop
     * @maxLength 10
     * @var string|null
     */
    public $unit;

    /**
     * @prop
     * @maxLength 20
     * @var string
     */
    public $name;

    /**
     * Sets the recipeId for the ingredient and checks whether the user owns this recipe (or it exists)
     *
     * @param integer $recipeId The id of the recipe
     * @param integer $userId The id of the user
     *
     * @return boolean True if succeeded, false if the recipe does not exist or is not owned by the user
     */
    public function setRecipeId($recipeId, $userId) {
        $recipe_count = Recipe::query(
            "id = ? AND id IN (SELECT id FROM recipes WHERE userId = ?)",
            [$recipeId, $userId]
        )->count();

        if ($recipe_count !== 1) {
            return false;
        }

        $this->editValue("recipeId", $recipeId, false, true);

        return true;
    }

    public static function getErrors($ingredient) {
        return Validation::getValidationErrorMessages($ingredient, [
            "recipeId" => ["Recipe"],
            "amount" => ["Amount"],
            "unit" => ["Unit", true],
            "name" => ["Name", true],
        ]);
    }

    /**
     * Returns a specific ingredient
     *
     * @param int $id The id of the ingredient
     * @param int|null $userId The id of the user, which needs to own the recipe, or null if it should be ignored
     *
     * @return Ingredient|null The found ingredient or null if not found
     */
    public static function getById($id, $userId = null) {
        if ($userId === null) {
            return self::get("id = ?", [$id])->getFirst();
        } else {
            return self::get(
                "id = ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
                [$id, $userId]
            );
        }
    }

    /**
     * Returns the ingredients for a specific recipe
     *
     * @param int $recipeId The id of the recipe
     * @param int|null $userId The id of the user, which needs to own the recipe, or null if it should be ignored
     *
     * @return Collection The found ingredients
     */
    public static function getByRecipeId($recipeId, $userId = null) {
        if ($userId === null) {
            return self::get("recipeId = ?", [$recipeId]);
        } else {
            return self::get(
                "recipeId = ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
                [$recipeId, $userId]
            );
        }
    }
}
