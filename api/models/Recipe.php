<?php

namespace API\models;

use API\inc\Validation;
use PAF\Model\Collection;
use PAF\Model\Model;

/**
 * @tablename recipes
 */
class Recipe extends Model {
    const FORBIDDEN_SORT_PROPERTIES = ["userId"];

    private $user = null;

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
     * @output false
     */
    public $userId;

    /**
     * @prop
     * @var boolean
     */
    public $public = false;

    /**
     * @prop
     * @maxLength 50
     * @var string
     */
    public $name;

    /**
     * @prop
     * @var string|null
     */
    public $description;

    /**
     * @prop
     * @maxLength 20
     * @var string|null
     */
    public $category;

    /**
     * @prop
     * @min 1
     * @var integer|null
     */
    public $portions;

    /**
     * @prop
     * @min 0
     * @max 4
     * @var integer|null
     */
    public $difficulty;

    /**
     * @prop
     * @var string|null
     */
    public $preparation;

    /**
     * @prop
     * @min 1
     * @var integer|null
     */
    public $preparationTime;

    /**
     * @prop
     * @min 1
     * @var integer|null
     */
    public $restTime;

    /**
     * @prop
     * @min 1
     * @var integer|null
     */
    public $cookTime;

    /**
     * @prop
     * @var timestamp
     * @editable false
     */
    public $publishDate;

    public function __construct() {
        parent::__construct();

        $this->editValue("publishDate", time(), false, true);
    }

    /**
     * The user model for the recipe
     */
    public function user() {
        if ($this->user === null || $this->user->id !== $this->userId) {
            $this->user = User::getById($this->userId);
        }

        return $this->user;
    }

    public function jsonSerialize($full = false) {
        $ret = array_merge(parent::jsonSerialize(), [
            "user" => $this->user(),
            "imagesCount" => RecipeImage::getAmountForRecipe($this->id),
        ]);

        if ($full) {
            $ret["ingredients"] = Ingredient::getByRecipeId($this->id);
        }

        return $ret;
    }

    public static function getErrors($recipe) {
        return Validation::getValidationErrorMessages($recipe, [
            "userId" => ["Recipe"],
            "public" => ["Visibility"],
            "name" => ["Name", true],
            "description" => ["Description", true],
            "portions" => ["Portions"],
            "difficulty" => ["Difficulty"],
            "preparation" => ["Preparation", true],
            "preparationTime" => ["Preparation time"],
            "restTime" => ["Rest time"],
            "cookTime" => ["Cooking time"],
        ]);
    }

    /**
     * Returns a specific recipe
     *
     * @param int $id The id of the recipe
     * @param int|null $userId The id of the user, which needs to own the recipe, or null if it should be ignored
     *
     * @return Recipe|null The found recipe or null if not found
     */
    public static function getById($id, $userId = null) {
        if ($userId === null) {
            return self::get("id = ?", [$id])->getFirst();
        } else {
            return self::get(
                "id = ? AND id IN (SELECT id FROM recipes WHERE userId = ?)",
                [$id, $userId]
            )->getFirst();
        }
    }

    /**
     * Returns the recipes for a user
     *
     * @param int $userId The id of the user
     *
     * @return Collection The found recipes
     */
    public static function getByUserId($userId) {
        return self::get("userId = ?", [$userId]);
    }

    /**
     * Returns a recipe-query-object for the given query, that the user has access to
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
                $whereClause = "($whereClause) AND public = 1";
            }
        } elseif (!$user->isAdmin) {
            if ($canEdit) {
                $whereClause = "($whereClause) AND userId = :userId";
            } else {
                $whereClause = "($whereClause) AND (userId = :userId OR public = 1)";
            }

            $values["userId"] = $user->id;
        }

        return self::query($whereClause, $values);
    }
}
