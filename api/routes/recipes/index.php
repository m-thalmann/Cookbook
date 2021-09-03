<?php

namespace API\routes;

use API\auth\Authorization;
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

        return Functions::pagination(Functions::sort($query));
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
        $search = "%{$req["params"]["search"]}%";

        $query = Recipe::getQueryForUser(
            "name LIKE :search OR description LIKE :search",
            ["search" => $search],
            Authorization::user(),
            false
        );

        return Functions::pagination(Functions::sort($query));
    })
    ->get('/category/{{:name}}', Authorization::middleware(false), function (
        $req
    ) {
        $query = Recipe::getQueryForUser(
            "category = :category",
            [
                "category" => $req["params"]["name"],
            ],
            Authorization::user(),
            false
        );

        return Functions::pagination(Functions::sort($query));
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
            return Response::badRequest(Recipe::getErrors($recipe));
        }

        foreach ($ingredients as $ing) {
            $ingredient = Ingredient::fromValues($ing);

            $ingredient->setRecipeId($recipe->id, Authorization::user()->id);

            try {
                $ingredient->save();
            } catch (InvalidException $e) {
                Database::get()->rollBack();
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
        $query = Recipe::getQueryForUser(
            "id = :id",
            [
                "id" => $req["params"]["id"],
            ],
            Authorization::user(),
            true
        );

        if ($query->delete()) {
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
