<?php

namespace API\routes;

use API\auth\Authorization;
use API\models\Ingredient;
use PAF\Model\InvalidException;
use PAF\Model\Model;
use PAF\Router\Response;

$group
    ->get('/list', Authorization::middleware(), function () {
        $stmt = Model::db()->prepare(
            "SELECT DISTINCT `name`, `unit` FROM ingredients WHERE recipeId IN (SELECT id FROM recipes WHERE userId = ?)"
        );

        if (!$stmt->execute([Authorization::user()->id])) {
            return Response::error();
        }

        return $stmt->fetchAll();
    })
    ->put('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        $ingredient = Ingredient::getById(
            $req["params"]["id"],
            Authorization::user()->isAdmin ? null : Authorization::user()->id
        );

        if ($ingredient === null) {
            return Response::notFound();
        }

        $ingredient->editValues($req["post"] ?? [], true);

        try {
            $ingredient->save();
        } catch (InvalidException $e) {
            return Response::badRequest(Ingredient::getErrors($ingredient));
        }

        return $ingredient;
    })
    ->delete('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        if (
            Ingredient::getQueryForUser("id = :id", [
                "id" => $req["params"]["id"],
                Authorization::user(),
                true,
            ])->delete()
        ) {
            return Response::ok();
        } else {
            return Response::error();
        }
    });
