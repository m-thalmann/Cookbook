<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\ApiException;
use API\inc\Validation;
use API\models\Ingredient;
use PAF\Model\Database;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/list', Authorization::middleware(), function () {
        $stmt = Database::get()->prepare(
            "SELECT DISTINCT `name`, `unit` FROM ingredients WHERE recipeId IN (SELECT id FROM recipes WHERE userId = ?)"
        );

        if (!$stmt->execute([Authorization::user()->id])) {
            throw ApiException::error("default", "Could not fetch ingredients");
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
            throw ApiException::badRequest(
                "validation",
                Validation::getErrorMessages($ingredient)
            );
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
            throw ApiException::error("default", "Error deleting ingredient");
        }
    });
