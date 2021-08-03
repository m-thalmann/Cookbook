<?php

use API\Auth\Authorization;
use API\Models\RecipeImage;
use PAF\Router\Response;

$group->delete('/id/{{i:id}}', Authorization::middleware(), function ($req) {
    if (
        RecipeImage::deleteById($req["params"]["id"], Authorization::user()->id)
    ) {
        return Response::ok();
    } else {
        return Response::notFound();
    }
});
