<?php

namespace API\Models;

use API\Config\Config;
use API\Inc\Validation;
use PAF\Model\DuplicateException;
use PAF\Model\Model;

/**
 * @tablename recipe_images
 */
class RecipeImage extends Model {
    const MIME_TYPES = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "jfif" => "image/jpeg",
        "png" => "image/png",
        "gif" => "image/gif",
    ];

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
     */
    public $recipeId;

    /**
     * @prop
     * @var string
     * @output false
     */
    public $path;

    /**
     * @prop
     * @var string
     */
    public $mimeType;

    /**
     * Adds a new recipe-image by moving it to the image-store folder
     * and inserting a new RecipeImage into the database
     *
     * @param int $recipeId The id of the recipe
     * @param string $tmpLocation The path of the image
     * @param string $fileExtension The file-type-extension
     *
     * @return RecipeImage The created recipe-image
     */
    public static function add($recipeId, $tmpLocation, $fileExtension) {
        if (!array_key_exists($fileExtension, self::MIME_TYPES)) {
            throw new \InvalidArgumentException("Not allowed file-type");
        }

        $mimeType = self::MIME_TYPES[$fileExtension];

        $finalPath =
            self::getImageStorePath() .
            "recipe_$recipeId-" .
            sha1_file($tmpLocation) .
            ".$fileExtension";

        if (file_exists($finalPath)) {
            throw new DuplicateException(
                "The same image already exists for this recipe"
            );
        }

        if (!move_uploaded_file($tmpLocation, $finalPath)) {
            throw new \Exception("Error uploading file");
        }

        try {
            $recipeImage = self::create([
                "recipeId" => $recipeId,
                "path" => $finalPath,
                "mimeType" => $mimeType,
            ]);

            $recipeImage->save();

            return $recipeImage;
        } catch (\Exception $e) {
            unlink($finalPath);
            throw $e;
        }
    }

    /**
     * Returns the path where the images are stored
     *
     * @return string
     */
    public static function getImageStorePath() {
        $path = Config::get('image_store');

        if ($path === null) {
            $path = ROOT_DIR . "/data/image_store/";
        } elseif ($path[strlen($path) - 1] !== "/") {
            $path .= "/";
        }

        return $path;
    }

    public static function getErrors($user) {
        return Validation::getValidationErrorMessages($user, [
            "recipeId" => ["Recipe"],
        ]);
    }

    /**
     * Returns the amount of images for a specific recipe
     *
     * @param int $id The id of the recipe
     * @param int|null $userId The id of the user, which needs to be able to view the recipe, or null if it should be ignored
     *
     * @return integer
     */
    public static function getAmountForRecipe($id, $userId = null) {
        if ($userId === null) {
            return self::query("recipeId = ?", [$id])->count();
        } else {
            return self::query(
                "recipeId = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND (public = 1 OR userId = ?))",
                [$id, $userId]
            )->count();
        }
    }

    /**
     * Deletes a specific recipe-image
     *
     * @param int $id The id of the recipe-image
     * @param int|null $userId The id of the user, which needs to own the recipe, or null if it should be ignored
     *
     * @return boolean
     */
    public static function deleteById($id, $userId = null) {
        if ($userId !== null) {
            if (
                self::query(
                    "id = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND userId = ?)",
                    [$id, $userId]
                )->count() !== 1
            ) {
                return false;
            }
        }

        $image = self::get("id = ?", [$id])->getFirst();

        if ($image !== null) {
            unlink($image->path);

            return $image->delete();
        } else {
            return false;
        }
    }
}
