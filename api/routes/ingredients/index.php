<?php

namespace API\routes;

use API\auth\Authorization;
use API\models\Ingredient;
use PAF\Model\InvalidException;
use PAF\Router\Response;

$group
    ->get('/search/{{:search}}', Authorization::middleware(), function ($req) {
        $search = $req["params"]["search"];

        if (strlen($search) < 3) {
            return Response::badRequest(
                "Search must be at least 3 characters long"
            );
        }

        $ingredients = Ingredient::getRaw(
            "name LIKE ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
            ["%$search%", Authorization::user()->id]
        );

        return array_map(function ($ingredient) {
            return [
                "name" => $ingredient["name"],
                "unit" => $ingredient["unit"],
            ];
        }, $ingredients);
    })
    ->put('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        $ingredient = Ingredient::getById(
            $req["params"]["id"],
            Authorization::user()->id
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
            Ingredient::query(
                "id = ? AND recipeId IN (SELECT id FROM recipes WHERE userId = ?)",
                [$req["params"]["id"], Authorization::user()->id]
            )->delete()
        ) {
            return Response::ok();
        } else {
            return Response::error();
        }
    });
