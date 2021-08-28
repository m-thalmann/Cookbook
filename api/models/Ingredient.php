<?php

namespace API\models;

use API\inc\Validation;
use PAF\Model\Collection;
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
     * @param integer|null $userId The id of the user, or null if it should be ignored
     *
     * @return boolean True if succeeded, false if the recipe does not exist or is not owned by the user
     */
    public function setRecipeId($recipeId, $userId) {
        if ($userId === null) {
            $recipeCount = Recipe::query("id = ?", [$recipeId])->count();
        } else {
            $recipeCount = Recipe::query(
                "id = ? AND id IN (SELECT id FROM recipes WHERE userId = ?)",
                [$recipeId, $userId]
            )->count();
        }

        if ($recipeCount !== 1) {
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
            )->getFirst();
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
            $query = self::query("recipeId = ?", [$recipeId]);
        } else {
            $query = self::query(
                "recipeId = ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
                [$recipeId, $userId]
            );
        }

        $query->orderBy("name");

        return $query->get();
    }

    /**
     * Returns a ingredient-query-object for the given query, that the user has access to
     *
     * @param string $whereClause The sql-where-clause for the query
     * @param array $values The values inserted safely into the query (named parameters)
     * @param User|null $user The user that has to be able to access the recipe
     * @param boolean $canEdit Whether the user needs to be able to edit the recipe or not (admin/owner)
     */
    public static function getQueryForUser(
        $whereClause = "1",
        $values = [],
        $user = null,
        $canEdit = false
    ) {
        if ($user === null) {
            if ($canEdit) {
                $whereClause = "0";
            } else {
                $whereClause = "($whereClause) AND recipeId IN (SELECT id FROM recipes WHERE public = 1)";
            }
        } elseif (!$user->isAdmin) {
            if ($canEdit) {
                $whereClause = "($whereClause) AND recipeId IN (SELECT id FROM recipes WHERE userId = :userId)";
            } else {
                $whereClause = "($whereClause) AND recipeId IN (SELECT id FROM recipes WHERE userId = :userId OR public = 1)";
            }

            $values["userId"] = $user->id;
        }

        return self::query($whereClause, $values);
    }
}
