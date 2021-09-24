<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\ApiException;
use API\inc\Functions;
use API\models\Ingredient;
use API\models\Recipe;
use API\models\RecipeImage;
use PAF\Model\Database;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware(false), function () {
        $query = Recipe::getQueryForUser("1", [], Authorization::user(), false);

        return Functions::pagination(
            Functions::sort($query, Recipe::FORBIDDEN_SORT_PROPERTIES)
        );
    })
    ->get('/id/{{i:id}}', Authorization::middleware(false), function ($req) {
        $recipe = Recipe::getQueryForUser(
            "id = :id",
            [
                "id" => $req["params"]["id"],
            ],
            Authorization::user(),
            false
        )
            ->get()
            ->getFirst();

        if ($recipe !== null) {
            return $recipe->jsonSerialize(true);
        } else {
            return Response::notFound();
        }
    })
    ->get('/search/{{:search}}', Authorization::middleware(false), function (
        $req
    ) {
        $search = "%" . urldecode($req["params"]["search"]) . "%";

        $query = Recipe::getQueryForUser(
            "name LIKE :search OR description LIKE :search",
            ["search" => $search],
            Authorization::user(),
            false
        );

        return Functions::pagination(
            Functions::sort($query, Recipe::FORBIDDEN_SORT_PROPERTIES)
        );
    })
    ->get('/category/{{:name}}', Authorization::middleware(false), function (
        $req
    ) {
        $query = Recipe::getQueryForUser(
            "category = :category",
            [
                "category" => urldecode($req["params"]["name"]),
            ],
            Authorization::user(),
            false
        );

        return Functions::pagination(
            Functions::sort($query, Recipe::FORBIDDEN_SORT_PROPERTIES)
        );
    })
    ->post('/', Authorization::middleware(), function ($req) {
        Database::get()->beginTransaction();

        $data = $req["post"] ?? [];

        $ingredients = $data["ingredients"] ?? [];

        unset($data["ingredients"]);

        $recipe = Recipe::fromValues($data);

        $recipe->userId = Authorization::user()->id;

        try {
            $recipe->save();
        } catch (InvalidException $e) {
            Database::get()->rollBack();
            throw ApiException::badRequest(
                "validation",
                Recipe::getErrors($recipe)
            );
        }

        foreach ($ingredients as $ing) {
            $ingredient = Ingredient::fromValues($ing);

            $ingredient->setRecipeId($recipe->id, Authorization::user()->id);

            try {
                $ingredient->save();
            } catch (InvalidException $e) {
                Database::get()->rollBack();

                throw ApiException::badRequest(
                    "validation_ingredients",
                    Ingredient::getErrors($ingredient)
                );
            } catch (DuplicateException $e) {
                throw ApiException::conflict(
                    "ingredient_name",
                    "An ingredient with this name already exists for this recipe"
                );
            }
        }

        Database::get()->commit();

        return Response::created($recipe->jsonSerialize(true));
    })
    ->put('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        $recipe = Recipe::getById(
            $req["params"]["id"],
            Authorization::user()->isAdmin ? null : Authorization::user()->id
        );

        if ($recipe === null) {
            return Response::notFound();
        }

        if ($req["post"] !== null && array_key_exists("userId", $req["post"])) {
            throw ApiException::forbidden(
                "recipe.change_ownership",
                "You can't change ownership"
            );
        }

        $recipe->editValues($req["post"] ?? [], true);

        try {
            $recipe->save();
        } catch (InvalidException $e) {
            throw ApiException::badRequest(
                "validation",
                Recipe::getErrors($recipe)
            );
        }

        return $recipe->jsonSerialize(true);
    })
    ->delete('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        $query = Recipe::getQueryForUser(
            "id = :id",
            [
                "id" => $req["params"]["id"],
            ],
            Authorization::user(),
            true
        );

        if ($query->delete()) {
            RecipeImage::deleteOrphanImages();
            return Response::ok();
        } else {
            throw ApiException::error("default", "Error deleting image");
        }
    })
    ->post('/id/{{i:id}}/ingredients', Authorization::middleware(), function (
        $req
    ) {
        $ingredient = Ingredient::fromValues($req["post"] ?? []);

        if (
            !$ingredient->setRecipeId(
                $req["params"]["id"],
                Authorization::user()->isAdmin
                    ? null
                    : Authorization::user()->id
            )
        ) {
            return Response::notFound();
        }

        try {
            $ingredient->save();
        } catch (InvalidException $e) {
            throw ApiException::badRequest(
                "validation",
                Ingredient::getErrors($ingredient)
            );
        } catch (DuplicateException $e) {
            throw ApiException::conflict(
                "ingredient_name",
                "An ingredient with this name already exists for this recipe"
            );
        }

        return Response::created($ingredient);
    })
    ->get('/id/{{i:id}}/images', Authorization::middleware(), function ($req) {
        return RecipeImage::getQueryForUser(
            "recipeId = :id",
            ["id" => $req["params"]["id"]],
            Authorization::user(),
            true
        )->get();
    })
    ->get(
        '/id/{{i:id}}/images/count',
        Authorization::middleware(false),
        function ($req) {
            return RecipeImage::query(
                "recipeId = :id",
                ["id" => $req["params"]["id"]],
                Authorization::user(),
                false
            )->count();
        }
    )
    ->get(
        '/id/{{i:id}}/images/number/{{i:number}}',
        Authorization::middleware(false),
        function ($req) {
            $image = RecipeImage::query(
                "recipeId = :id",
                ["id" => $req["params"]["id"]],
                Authorization::user(),
                false
            )
                ->limit(1)
                ->offset($req["params"]["number"])
                ->get()
                ->getFirst();

            if ($image === null) {
                return Response::notFound();
            }

            return Functions::outputRecipeImage(
                $image,
                true,
                $_GET["maxSize"] ?? null
            );
        }
    )
    ->post('/id/{{i:id}}/images', Authorization::middleware(), function ($req) {
        if (
            Recipe::getQueryForUser(
                "id = :id",
                [
                    "id" => $req["params"]["id"],
                ],
                Authorization::user(),
                true
            )->count() === 0
        ) {
            return Response::notFound();
        }

        if (
            !isset($_FILES['image']) ||
            !isset($_FILES['image']['error']) ||
            is_array($_FILES['image']['error'])
        ) {
            throw ApiException::badRequest("image.no_file", "No file");
        }

        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw ApiException::badRequest("image.no_file", "No file");
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw ApiException::badRequest(
                    "image.too_large",
                    "Exceeded filesize limit"
                );
            default:
                throw ApiException::error("default", "Upload error");
        }

        $name = $_FILES['image']['name'];

        $tmpLocation = $_FILES['image']['tmp_name'];
        $fileExtension = substr($name, strrpos($name, '.') + 1);

        try {
            $image = RecipeImage::add(
                $req["params"]["id"],
                $tmpLocation,
                $fileExtension
            );
        } catch (DuplicateException $e) {
            throw ApiException::conflict("recipe_image", $e->getMessage());
        }

        return Response::created($image);
    });
