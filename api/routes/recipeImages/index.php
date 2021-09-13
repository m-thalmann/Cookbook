<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\RecipeImage;
use PAF\Router\Response;

$group
    ->get('/id/{{i:id}}', Authorization::middleware(false), function ($req) {
        $recipeImage = RecipeImage::getQueryForUser(
            "id = :id",
            ["id" => $req["params"]["id"]],
            Authorization::user(),
            false
        )
            ->get()
            ->getFirst();

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
                Authorization::user()->isAdmin
                    ? null
                    : Authorization::user()->id
            )
        ) {
            RecipeImage::deleteOrphanImages();
            return Response::ok();
        } else {
            return Response::notFound();
        }
    });
