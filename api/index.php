<?php

namespace API;

use API\config\Config;

use API\inc\ApiException;
use PAF\Model\Database;
use PAF\Router\Router;

try {
    // Libraries
    require_once __DIR__ . '/lib/PAF/src/autoload.php';
    require_once __DIR__ . '/lib/PHP-JWT/JWT.php';

    // Autoload
    require_once __DIR__ . '/autoload.php';

    // Config
    Config::loadBaseConfig(Config::getBaseConfigPath());

    if (Config::getBaseConfig("production", true)) {
        error_reporting(E_ALL ^ E_WARNING);
    }

    // Database
    Database::setDatabase(
        'mysql',
        Config::getBaseConfig('database.host'),
        Config::getBaseConfig('database.database'),
        Config::getBaseConfig('database.user'),
        Config::getBaseConfig('database.password'),
        Config::getBaseConfig('database.charset')
    );

    Config::loadConfig();

    // Constants
    define('ROOT_URL', Config::getBaseConfig('root_url'));

    define('ROOT_DIR', __DIR__);

    // Router
    Router::setHeaders([
        "Access-Control-Allow-Headers" => "Content-Type, Authorization",
        "Access-Control-Expose-Headers" => "X-Logout",
    ]);

    Router::init(ROOT_URL, true);

    // Routes

    try {
        try {
            Router::addRoutes()->get('/', function () {
                return [
                    "info" => "Cookbook API",
                ];
            });

            Router::group('/admin', __DIR__ . '/routes/admin/index.php');
            Router::group('/auth', __DIR__ . '/routes/auth/index.php');
            Router::group(
                '/categories',
                __DIR__ . '/routes/categories/index.php'
            );
            Router::group(
                '/ingredients',
                __DIR__ . '/routes/ingredients/index.php'
            );
            Router::group(
                '/recipeImages',
                __DIR__ . '/routes/recipeImages/index.php'
            );
            Router::group('/recipes', __DIR__ . '/routes/recipes/index.php');
            Router::group('/users', __DIR__ . '/routes/users/index.php');

            if (!Router::execute()) {
                throw ApiException::notImplemented(
                    "route_not_found",
                    "Route not found"
                );
            }
        } catch (\InvalidArgumentException $e) {
            throw ApiException::badRequest("default", $e->getMessage());
        } catch (\PDOException $e) {
            switch ($e->getCode()) {
                case 1042:
                case 1043:
                case 1044:
                case 1045:
                case 2002:
                    throw ApiException::error(
                        "database.connection",
                        "Database connection could not be established"
                    );
                default:
                    throw ApiException::error(
                        "database.default",
                        $e->getMessage()
                    );
            }
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ApiException::error("default", $e->getMessage());
        }
    } catch (ApiException $e) {
        Router::output($e->getResponse());
    }
} catch (\Exception $e) {
    @header("Content-Type: application/json");
    @http_response_code(500);

    echo json_encode($e->getMessage());
}
