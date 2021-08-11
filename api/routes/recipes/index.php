<?php

namespace API\Routes;

use API\Auth\Authorization;
use API\inc\Functions;
use API\Models\Ingredient;
use API\Models\Recipe;
use API\Models\RecipeImage;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Model\Model;
use PAF\Router\Response;

$group
    ->get('/', Authorization::middleware(false), function () {
        if (Authorization::isAuthorized()) {
            $query = Recipe::query("public = 1 OR userId = ?", [
                Authorization::user()->id,
            ]);
        } else {
            $query = Recipe::query("public = 1");
        }

        return Functions::pagination(Functions::sort($query));
    })
    ->get('/id/{{i:id}}', Authorization::middleware(false), function ($req) {
        $recipe = null;
        if (Authorization::isAuthorized()) {
            $recipe = Recipe::get("id = ? AND (public = 1 OR userId = ?)", [
                $req["params"]["id"],
                Authorization::user()->id,
            ])->getFirst();
        } else {
            $recipe = Recipe::get("id = ? AND public = 1", [
                $req["params"]["id"],
            ])->getFirst();
        }

        if ($recipe !== null) {
            return $recipe->jsonSerialize(true);
        } else {
            return Response::notFound();
        }
    })
    ->get('/search/{{:search}}', Authorization::middleware(false), function (
        $req
    ) {
        $search = "%{$req["params"]["search"]}%";

        if (Authorization::isAuthorized()) {
            $query = Recipe::query(
                "(name LIKE :search OR description LIKE :search) AND (public = 1 OR userId = :userId)",
                [
                    "search" => $search,
                    "userId" => Authorization::user()->id,
                ]
            );
        } else {
            $query = Recipe::query(
                "(name LIKE :search OR description LIKE :search) AND public = 1",
                ["search" => $search]
            );
        }

        return Functions::pagination(Functions::sort($query));
    })
    ->get('/category/{{:name}}', Authorization::middleware(false), function (
        $req
    ) {
        if (Authorization::isAuthorized()) {
            $query = Recipe::query(
                "category = ? AND (public = 1 OR userId = ?)",
                [$req["params"]["name"], Authorization::user()->id]
            );
        } else {
            $query = Recipe::query("category = ? AND public = 1", [
                $req["params"]["name"],
            ]);
        }

        return Functions::pagination(Functions::sort($query));
    })
    ->post('/', Authorization::middleware(), function ($req) {
        Model::db()->beginTransaction();

        $data = $req["post"] ?? [];

        $ingredients = $data["ingredients"] ?? [];

        unset($data["ingredients"]);

        $recipe = Recipe::fromValues($data);

        $recipe->userId = Authorization::user()->id;

        try {
            $recipe->save();
        } catch (InvalidException $e) {
            Model::db()->rollBack();
            return Response::badRequest(Recipe::getErrors($recipe));
        }

        foreach ($ingredients as $ing) {
            $ingredient = Ingredient::fromValues($ing);

            $ingredient->setRecipeId($recipe->id, Authorization::user()->id);

            try {
                $ingredient->save();
            } catch (InvalidException $e) {
                Model::db()->rollBack();
                return Response::badRequest([
                    "ingredients" => Ingredient::getErrors($ingredient),
                ]);
            } catch (DuplicateException $e) {
                return Response::conflict([
                    "info" =>
                        "An ingredient with this name already exists for this recipe",
                ]);
            }
        }

        Model::db()->commit();

        return Response::created($recipe->jsonSerialize(true));
    })
    ->put('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        $recipe = Recipe::getById(
            $req["params"]["id"],
            Authorization::user()->id
        );

        if ($recipe === null) {
            return Response::notFound();
        }

        if ($req["post"] !== null && array_key_exists("userId", $req["post"])) {
            return Response::forbidden([
                "info" => "You can't change ownership",
            ]);
        }

        $recipe->editValues($req["post"] ?? [], true);

        try {
            $recipe->save();
        } catch (InvalidException $e) {
            return Response::badRequest(Recipe::getErrors($recipe));
        }

        return $recipe->jsonSerialize(true);
    })
    ->delete('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        if (
            Recipe::query(
                "id = ? AND userId = ?",
                [$req["params"]["id"], Authorization::user()->id]
            )->delete()
        ) {
            return Response::ok();
        } else {
            return Response::error();
        }
    })
    ->post('/id/{{i:id}}/ingredients', Authorization::middleware(), function (
        $req
    ) {
        $ingredient = Ingredient::fromValues($req["post"] ?? []);

        if (
            !$ingredient->setRecipeId(
                $req["params"]["id"],
                Authorization::user()->id
            )
        ) {
            return Response::notFound();
        }

        try {
            $ingredient->save();
        } catch (InvalidException $e) {
            return Response::badRequest(Ingredient::getErrors($ingredient));
        } catch (DuplicateException $e) {
            return Response::conflict([
                "info" =>
                    "An ingredient with this name already exists for this recipe",
            ]);
        }

        return Response::created($ingredient);
    })
    ->get('/id/{{i:id}}/images', Authorization::middleware(), function ($req) {
        return RecipeImage::get(
            "recipeId = ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
            [$req["params"]["id"], Authorization::user()->id]
        );
    })
    ->get(
        '/id/{{i:id}}/images/count',
        Authorization::middleware(false),
        function ($req) {
            if (Authorization::isAuthorized()) {
                return RecipeImage::query(
                    "recipeId = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND (public = 1 OR userId = ?))",
                    [$req["params"]["id"], Authorization::user()->id]
                )->count();
            } else {
                return RecipeImage::query(
                    "recipeId = ? AND recipeId IN (SELECT id FROM recipes WHERE public = 1)",
                    [$req["params"]["id"]]
                )->count();
            }
        }
    )
    ->get(
        '/id/{{i:id}}/images/number/{{i:number}}',
        Authorization::middleware(false),
        function ($req) {
            if (Authorization::isAuthorized()) {
                $image = RecipeImage::query(
                    "recipeId = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND (public = 1 OR userId = ?))",
                    [$req["params"]["id"], Authorization::user()->id]
                )
                    ->limit(1)
                    ->offset($req["params"]["number"])
                    ->get()
                    ->getFirst();
            } else {
                $image = RecipeImage::query(
                    "recipeId = ? AND recipeId IN (SELECT id FROM recipes WHERE public = 1)",
                    [$req["params"]["id"]]
                )
                    ->limit(1)
                    ->offset($req["params"]["number"])
                    ->get()
                    ->getFirst();
            }

            if ($image === null) {
                return Response::notFound();
            }



            return Functions::outputRecipeImage($image);
        }
    )
    ->post('/id/{{i:id}}/images', Authorization::middleware(), function ($req) {
        if (
            Recipe::query("id = ? AND userId = ?", [
                $req["params"]["id"],
                Authorization::user()->id,
            ])->count() === 0
        ) {
            return Response::notFound();
        }

        if (
            !isset($_FILES['image']) ||
            !isset($_FILES['image']['error']) ||
            is_array($_FILES['image']['error'])
        ) {
            return Response::badRequest(["info" => "No file"]);
        }

        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return Response::badRequest(["info" => "No file"]);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return Response::badRequest([
                    "info" => 'Exceeded filesize limit',
                ]);
            default:
                return Response::error(["info" => 'Upload error']);
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
        } catch (\InvalidArgumentException $e) {
            return Response::badRequest(["info" => $e->getMessage()]);
        } catch (DuplicateException $e) {
            return Response::conflict(["info" => $e->getMessage()]);
        }

        return Response::created($image);
    });
