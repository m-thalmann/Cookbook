<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\RecipeImage;
use PAF\Router\Response;

$group
    ->get('/id/{{i:id}}', Authorization::middleware(false), function ($req) {
        if (Authorization::isAuthorized()) {
            $recipeImage = RecipeImage::get(
                "id = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND (public = 1 OR userId = ?))",
                [$req["params"]["id"], Authorization::user()->id]
            )->getFirst();
        } else {
            $recipeImage = RecipeImage::get(
                "id = ? AND EXISTS (SELECT * FROM recipes WHERE id = recipeId AND public = 1)",
                [$req["params"]["id"]]
            )->getFirst();
        }

        if ($recipeImage !== null) {
            return Functions::outputRecipeImage(
                $recipeImage,
                true,
                $_GET["maxSize"] ?? null
            );
        } else {
            return Response::notFound();
        }
    })
    ->delete('/id/{{i:id}}', Authorization::middleware(), function ($req) {
        if (
            RecipeImage::deleteById(
                $req["params"]["id"],
                Authorization::user()->id
            )
        ) {
            return Response::ok();
        } else {
            return Response::notFound();
        }
    });
