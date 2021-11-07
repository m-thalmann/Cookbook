<?php

namespace API\routes;

use API\auth\Authorization;
use API\models\Recipe;
use PAF\Model\Database;

$group->get("/", Authorization::middleware(false), function () {
    if (Authorization::isAuthorized()) {
        if (Authorization::user()->isAdmin) {
            $stmt = Database::get()->query(
                "SELECT DISTINCT category FROM recipes WHERE category IS NOT NULL ORDER BY category ASC"
            );
        } else {
            $stmt = Database::get()->prepare(
                "SELECT DISTINCT category FROM recipes WHERE category IS NOT NULL AND (public = 1 OR userId = ?) ORDER BY category ASC"
            );
            $stmt->execute([Authorization::user()->id]);
        }
    } else {
        $stmt = Database::get()->query(
            "SELECT DISTINCT category FROM recipes WHERE category IS NOT NULL AND public = 1 ORDER BY category ASC"
        );
    }

    return array_map(function ($category) {
        $thumbnailRecipeData = Recipe::getQueryForUser(
            "category = :category AND EXISTS (SELECT * FROM recipe_images WHERE recipeId = recipes.id)",
            ["category" => $category["category"]],
            Authorization::user(),
            false
        )
            ->limit(1)
            ->getRaw(false);

        $thumbnailRecipe = null;

        if ($thumbnailRecipeData && count($thumbnailRecipeData) > 0) {
            $thumbnailRecipe = $thumbnailRecipeData[0]["id"];
        }

        return [
            "name" => $category["category"],
            "thumbnailRecipeId" => $thumbnailRecipe,
        ];
    }, $stmt->fetchAll());
});
