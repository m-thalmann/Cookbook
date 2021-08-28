<?php

namespace API\routes;

$group->group('/recipes', __DIR__ . '/recipes/index.php');
$group->group('/users', __DIR__ . '/users/index.php');
