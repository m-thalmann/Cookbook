<?php

namespace API\Routes;

use API\Auth\Authorization;
use API\Models\Ingredient;
use API\Models\Recipe;
use PAF\Model\DuplicateException;
use PAF\Model\InvalidException;
use PAF\Model\Model;
use PAF\Router\Response;

$group
    ->get('/', function ($req) {
        if (Authorization::middleware()($req)->isOk()) {
            return Recipe::query("public = 1 OR userId = ?", [
                Authorization::user()->id,
            ])->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        } else {
            return Recipe::query("public = 1")->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        }
    })
    ->get('/id/{{i:id}}', function ($req) {
        $recipe = null;
        if (Authorization::middleware()($req)->isOk()) {
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
    ->get('/search/{{:search}}', function ($req) {
        $search = "%{$req["paramas"]["search"]}%";

        if (Authorization::middleware()($req)->isOk()) {
            return Recipe::query(
                "name LIKE :search OR description LIKE :search AND (public = 1 OR userId = :userId)",
                [
                    "search" => $search,
                    "userId" => Authorization::user()->id,
                ]
            )->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        } else {
            return Recipe::query(
                "name LIKE :search OR description LIKE :search AND public = 1",
                ["search" => $search]
            )->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        }
    })
    ->get('/category/{{:name}}', function ($req) {
        if (Authorization::middleware()($req)->isOk()) {
            return Recipe::query(
                "category = ? AND (public = 1 OR userId = ?)",
                [$req["params"]["name"], Authorization::user()->id]
            )->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        } else {
            return Recipe::query("category = ? AND public = 1", [
                $req["params"]["name"],
            ])->pagination(
                intval($_GET["items_per_page"] ?? 10),
                intval($_GET["page"] ?? 0)
            );
        }
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
                "id = ? AND id IN (SELECT id FROM recipes WHERE userId = ?)",
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
    });
