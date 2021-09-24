<?php

namespace API\models;

use API\config\Config;
use API\inc\ApiException;
use PAF\Model\Database;
use PAF\Model\DuplicateException;
use PAF\Model\Model;

/**
 * @tablename recipe_images
 */
class RecipeImage extends Model {
    /**
     * @var array Properties (key) checked in validation. The value is a boolean, whether the property is a string or not
     */
    const VALIDATION_PROPERTIES = [
        "recipeId" => false,
    ];

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
     * @throws ApiException
     * @throws DuplicateException
     * @throws \PAF\Model\InvalidException
     *
     * @return RecipeImage The created recipe-image
     */
    public static function add($recipeId, $tmpLocation, $fileExtension) {
        if (!array_key_exists($fileExtension, self::MIME_TYPES)) {
            throw ApiException::badRequest(
                "image.not_allowed_type",
                "Not allowed file-type"
            );
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
            throw ApiException::error(
                "image.error_uploading",
                "Error uploading file"
            );
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
     * Deletes all images that have no entry in the database
     */
    public static function deleteOrphanImages() {
        $images = scandir(self::getImageStorePath());

        $databaseImages = Database::get()
            ->query("SELECT `path` FROM `recipe_images`")
            ->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($images as $image) {
            if (
                $image[0] !== "." &&
                !in_array(self::getImageStorePath() . $image, $databaseImages)
            ) {
                unlink(self::getImageStorePath() . $image);
            }
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

    /**
     * Returns a recipeImage-query-object for the given query, that the user has access to
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
