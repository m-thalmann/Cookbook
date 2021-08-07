<?php

namespace API\Models;

use API\Inc\Validation;
use PAF\Model\Model;

/**
 * @tablename recipes
 */
class Recipe extends Model {
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
     * @maxLength 20
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
     * @maxLength 10
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
            );
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
}
