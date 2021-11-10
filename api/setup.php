<?php

namespace API;

use API\config\Config;
use API\config\ConfigSettings;
use API\inc\Functions;
use API\models\RecipeImage;
use API\models\User;
use PAF\Model\Database;

require_once __DIR__ . '/lib/PAF/src/autoload.php';
require_once __DIR__ . '/autoload.php';

define('ROOT_DIR', __DIR__);

$baseConfigLoaded = false;
$configLoaded = false;
$configSaved = true;
$databaseError = false;
$databaseConnected = false;
$createUserError = null;

const PASSWORD_PLACEHOLDER = "<db_password>";

if (is_file(Config::getBaseConfigPath())) {
    try {
        Config::loadBaseConfig(Config::getBaseConfigPath());

        $baseConfigLoaded = true;

        if (Config::getBaseConfig("setup_complete", true)) {
            header("HTTP/401 Unauthorized");
            exit();
        }
    } catch (\Exception $e) {
        echo "FATAL: Error loading base config: " . $e->getMessage();
    }
}

function getConfigValue($path) {
    global $baseConfigLoaded;
    global $configSaved;

    if (!$configSaved) {
        $postPath = str_replace(".", "-", $path);

        if (array_key_exists($postPath, $_POST)) {
            return $_POST[$postPath];
        }
    }

    if (!$baseConfigLoaded) {
        return "";
    }

    return Config::getBaseConfig($path, "");
}

function getPostValue($path) {
    return isset($_POST[$path]) ? $_POST[$path] : null;
}

function connectDB() {
    global $databaseError;
    $databaseError = null;
    $databaseConnected = false;

    $databaseHost = Config::getBaseConfig('database.host');
    $databaseUser = Config::getBaseConfig('database.user');
    $databasePassword = Config::getBaseConfig('database.password');
    $databaseDatabase = Config::getBaseConfig('database.database');

    if (
        $databaseHost &&
        $databaseUser &&
        $databasePassword &&
        $databaseDatabase
    ) {
        try {
            Database::setDatabase(
                'mysql',
                $databaseHost,
                $databaseDatabase,
                $databaseUser,
                $databasePassword,
                Config::getBaseConfig('database.charset', "utf8")
            );

            Database::get()->query("SELECT 1");

            global $databaseConnected;
            $databaseConnected = true;

            return true;
        } catch (\Exception $e) {
            $databaseError = $e->getMessage();

            Database::unregisterProvider();
        }
    }

    return false;
}

function getAPIUrl(){
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    $url = ($isHttps ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

    if(($isHttps && $_SERVER['SERVER_PORT'] != 443) || (!$isHttps && $_SERVER['SERVER_PORT'] != 80)){
        $url .= ":$_SERVER[SERVER_PORT]";
    }

    $url .= Config::getBaseConfig("root_url");

    return $url;
}

if (array_key_exists("complete", $_GET)) {
    Config::editBaseConfig(function ($config) {
        if ($config === null) {
            $config = [];
        }

        if (array_key_exists("setup_complete", $config)) {
            unset($config["setup_complete"]);
        }

        return $config;
    });

    echo "Setup completed successfully! Go to the \"Admin Panel\" to change further settings.";
    exit();
}

if (
    !file_exists(ConfigSettings::getConfigSecretPath()) ||
    filesize(ConfigSettings::getConfigSecretPath()) === 0
) {
    file_put_contents(
        ConfigSettings::getConfigSecretPath(),
        Functions::getRandomString()
    );
}

if (connectDB()) {
    try {
        Config::loadConfig();
        $configLoaded = true;
    } catch (\Exception $e) {
    }
}

if (array_key_exists("fillDB", $_GET)) {
    if ($databaseConnected) {
        $tables = Database::get()
            ->query("SHOW TABLES")
            ->fetchAll(\PDO::FETCH_COLUMN);

        Database::get()->beginTransaction();
        Database::get()->query("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($tables as $table) {
            Database::get()->query("DROP TABLE `$table`");
        }

        Database::get()->query("SET FOREIGN_KEY_CHECKS = 1");

        $dbSQL = file_get_contents(__DIR__ . "/database/db.sql");

        Database::get()->exec($dbSQL);

        Database::get()->commit();

        Config::edit("token.secret", Functions::getRandomString());
        Config::edit("password.secret", Functions::getRandomString());

        Config::writeConfig();

        RecipeImage::deleteOrphanImages();

        header('Location: setup.php');
        exit;
    }
}

if (array_key_exists("addUser", $_GET) && $databaseConnected) {
    $user = User::fromValues([
        "email" => $_POST["user-email"],
        "name" => $_POST["user-name"],
        "password" => $_POST["user-password"],
    ]);

    $user->setIsAdmin(true);
    $user->clearEmailVerification();

    try {
        $user->save();

        header('Location: setup.php');
        exit();
    } catch (\Exception $e) {
        $createUserError = $e->getMessage();
    }
}

if (array_key_exists("newConfigSecret", $_GET) && $_GET["newConfigSecret"] !== "ok") {
    $secret = Functions::getRandomString();
    ConfigSettings::updateConfigSecret($secret);

    file_put_contents(ConfigSettings::getConfigSecretPath(), $secret);

    header('Location: setup.php?newConfigSecret=ok');
}

if (!empty($_POST)) {
    if (
        Config::editBaseConfig(function ($config) {
            if ($config === null) {
                $config = [];
            }

            if (!array_key_exists("root_url", $config)) {
                $config["root_url"] = "";
            }
            if (!array_key_exists("production", $config)) {
                $config["production"] = true;
            }
            if (!array_key_exists("database", $config)) {
                $config["database"] = [];
            }
            if (!array_key_exists("image_store", $config)) {
                $config["image_store"] = null;
            }

            $rootUrl = getPostValue("root_url");
            $imageStore = getPostValue("image_store");

            $databaseHost = getPostValue("database-host");
            $databaseUser = getPostValue("database-user");
            $databasePassword = getPostValue("database-password");
            $databaseDatabase = getPostValue("database-database");

            if ($rootUrl !== null) {
                $config["root_url"] = trim($rootUrl);
            }
            if ($imageStore !== null) {
                $imageStore = trim($imageStore);
                $config["image_store"] = $imageStore ? $imageStore : null;
            }

            if ($databaseHost !== null) {
                $config["database"]["host"] = $databaseHost;
            }
            if ($databaseUser !== null) {
                $config["database"]["user"] = $databaseUser;
            }
            if (
                $databasePassword !== null &&
                $databasePassword !== PASSWORD_PLACEHOLDER
            ) {
                $config["database"]["password"] = $databasePassword;
            }
            if ($databaseDatabase !== null) {
                $config["database"]["database"] = $databaseDatabase;
            }

            if (!array_key_exists("charset", $config["database"])) {
                $config["database"]["charset"] = "utf8";
            }

            $config["setup_complete"] = false;

            return $config;
        })
    ) {
        $baseConfigLoaded = true;
        $configSaved = true;

        if (!$databaseConnected) {
            connectDB();
        }
    } else {
        $configSaved = false;
    }
}

$imageStoreWritable = $configLoaded && @is_writable(RecipeImage::getImageStorePath());

$adminUsers =
    $configLoaded && $databaseConnected ? User::get("isAdmin = 1") : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookbook Setup</title>

    <link rel="preconnect" href="https://fonts.gstatic.com" />

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            height: fit-content;
        }

        body {
            margin: 0;
            font-family: Roboto, 'Helvetica Neue', sans-serif;

            text-align: center;
            background: #e6e6e6;
        }

        strong{
            font-weight: 500;
        }

        .warn{
            color: #f12;
        }

        input[type="submit"], .button, button{
            border: none;
            border-radius: 0.25em;
            padding: 0.25em 0.5em;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;

            color: #fff;
            background: #6D4C41;
            vertical-align: middle;

            -webkit-box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);
            -moz-box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);
            box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);
        }
        
        input[type="submit"]:hover, .button:hover, button:hover{
            -webkit-box-shadow: 0 0 10px 0 rgba(0,0,0,0.5);
            -moz-box-shadow: 0 0 10px 0 rgba(0,0,0,0.5);
            box-shadow: 0 0 10px 0 rgba(0,0,0,0.5);
        }

        table{
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        th, td{
            border: 1px solid #ccc;
        }

        th{
            font-weight: normal;
            border-bottom: 2px solid #ccc;
        }

        ul{
            padding: 0;
        }

        ul li span{
            vertical-align: middle;
            display: inline-block;
        }

        .spinning{
            animation: spinning 1s infinite;
        }

        @keyframes spinning{
            0%{
                transform: rotate(360deg);
            }
            100%{
                transform: rotate(0deg);
            }
        }

        header{
            margin-bottom: 2rem;
            padding: 1em;
            
            -webkit-box-shadow: 0px 4px 10px 0px rgba(0,0,0,0.5);
            -moz-box-shadow: 0px 4px 10px 0px rgba(0,0,0,0.5);
            box-shadow: 0px 4px 10px 0px rgba(0,0,0,0.5);

            background: #3d3d3d;
            color: #eee;

            position: sticky;
            top: 0;
        }

        header h1{
            margin: 0;
            font-weight: 400;
        }

        header h1 span{
            vertical-align: middle;
        }

        header h1 .material-icons{
            font-size: 1.25em;
        }

        main{
            padding: 0 1em 1em 1em;
        }

        section{
            -webkit-box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);
            -moz-box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);
            box-shadow: 0 0 15px 0 rgba(0,0,0,0.25);

            border-radius: 1em;
            background: #fff;

            max-width: 500px;
            margin: 0 auto 2rem;
            padding: 0.5em 0.5em 1em;
        }

        section h2{
            font-weight: normal;
            margin: 0 0 1rem 0;
        }

        section h2 span{
            vertical-align: middle;
            display: inline-block;
        }

        section h2 span.material-icon{
            margin-right: 0.75em;
        }

        section .field{
            margin-bottom: 1em;
        }

        section .field label > * {
            vertical-align: middle;
        }

        section .field label, section .field label .info{
            display: block;
            margin-bottom: 0.5em;
        }

        section .field label .material-icons{
            margin-left: 0.1em;
            color: #19a3ff;
            cursor: pointer;
        }

        section .field label .info{
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.2s;
            color: #3d3d3d;
            font-size: 0.75em;
        }

        section .field label .material-icons:hover + .info{
            max-height: 5em;
        }

        section .field input{
            border-radius: 0.25em;
            border: 1px solid #ccc;
            padding: 0.25em 0.5em;
        }
    </style>

    <script>
        function setAPIWorks(works){
            var icon = document.getElementById("api_reachable_icon");

            icon.classList.remove("spinning");

            if(works){
                icon.style.color = "limegreen";
                icon.innerHTML = "check";
            }else{
                icon.style.color = "orange";
                icon.innerHTML = "warning";
            }
        }

        fetch('<?=getAPIUrl(); ?>/info').then((val) => {
            setAPIWorks(val.ok);
        }).catch((err) => {
            setAPIWorks(false);
        })
    </script>
</head>
<body>
    <header>
        <h1>
            <span class="material-icons">construction</span>
            <span>Cookbook Setup</span>
        </h1>
    </header>

    <main>
        <form action="setup.php" method="post">
            <section>
                <h2>
                    <span class="material-icons">tune</span>
                    <span>General</span>
                </h2>

                <div class="field">
                    <label for="root_url">
                        <span>Root URL</span>
                        <span class="material-icons">info</span>

                        <span class="info">
                            The base path where to api is reachable (used for routing). E.g.:
                            <ul>
                                <li>https://example.com/cookbook/api &RightArrow; /cookbook/api</li>
                                <li>https://cookbook.example.com/ &RightArrow; <i>(empty)</i></li>
                            </ul>
                        </span>
                    </label>
                    <input type="text" placeholder="/api" name="root_url" id="root_url" value="<?= getConfigValue(
                        "root_url"
                    ) ?>">
                </div>

                <div class="field">
                    <label for="image_store">
                        <span>Image-store path <small>(default empty)</small></span>
                        <span class="material-icons">info</span>

                        <span class="info">
                            The location where the image-files should be stored.<br />
                            <strong>Attention:</strong> The web-user (www-data) needs to be able to read &amp; write.<br />
                            Leave empty to use default location in <u>api/data/image_store</u>
                        </span>
                    </label>
                    <input type="text" placeholder="/etc/cookbook/data" name="image_store" id="image_store" value="<?= getConfigValue(
                        "image_store"
                    ) ?>">
                </div>

                <a href="setup.php?newConfigSecret" class="button">Set new config-secret</a>
                <?php if(array_key_exists("newConfigSecret", $_GET) && $_GET["newConfigSecret"] === "ok") { ?>
                <span class="material-icons" style="color: limegreen; vertical-align: middle">check</span>
                <?php } ?>
            </section>
        
            <section>
                <h2>
                    <span class="material-icons">storage</span>
                    <span>MySQL Database</span>
                    <?php if ($databaseConnected) { ?>
                    <span class="material-icons" style="color: limegreen">check</span>
                    <?php } ?>
                </h2>

                <?php if ($databaseError) { ?>
                <p><span class="warn">Could not connect to database:</span><br/><small><?= $databaseError ?></small></p>
                <?php } ?>

                <div class="field">
                    <label for="database-host">
                        <span>Host</span>
                    </label>
                    <input type="text" placeholder="example.com" name="database-host" id="database-host" value="<?= getConfigValue(
                        "database.host"
                    ) ?>">
                </div>
                <div class="field">
                    <label for="database-user">
                        <span>Username</span>
                    </label>
                    <input type="text" placeholder="root" name="database-user" id="database-user" value="<?= getConfigValue(
                        "database.user"
                    ) ?>">
                </div>
                <div class="field">
                    <label for="database-password">
                        <span>Password</span>
                    </label>
                    <input type="password" placeholder="password" name="database-password" id="database-password" value="<?= getConfigValue(
                        "database.password"
                    )
                        ? PASSWORD_PLACEHOLDER
                        : "" ?>">
                </div>
                <div class="field">
                    <label for="database-database">
                        <span>Database</span>
                    </label>
                    <input type="text" placeholder="cookbook" name="database-database" id="database-database" value="<?= getConfigValue(
                        "database.database"
                    ) ?>">
                </div>

                <?php if ($databaseConnected) { ?>
                <div class="fillDB">
                    <a
                        href="setup.php?fillDB"
                        onclick="return confirm('This will delete all existing data and images. Continue?')"
                        class="button"
                    >
                        Reset database &amp; create tables
                    </a>
                </div>
                <?php } ?>
            </section>

            <section style="padding: 1em">
                <ul>
                    <li>
                        <span>Created config file</span>
                        <?php if ($baseConfigLoaded) { ?>
                        <span class="material-icons" style="color: limegreen">check</span>
                        <?php } else { ?>
                        <span class="material-icons" style="color: red">clear</span>
                        <?php } ?>
                    </li>
                    <li>
                        <span>API reachable</span>
                        <span class="material-icons spinning" id="api_reachable_icon">loop</span>
                    </li>
                    <li>
                        <span>Image directory writable</span>
                        <?php if ($imageStoreWritable) { ?>
                        <span class="material-icons" style="color: limegreen">check</span>
                        <?php } else { ?>
                        <span class="material-icons" style="color: red">clear</span>
                        <?php } ?>
                    </li>
                    <li>
                        <span>Database-connection</span>
                        <?php if ($databaseConnected) { ?>
                        <span class="material-icons" style="color: limegreen">check</span>
                        <?php } else { ?>
                        <span class="material-icons" style="color: red">clear</span>
                        <?php } ?>
                    </li>
                    <li>
                        <span>Created database tables</span>
                        <?php if ($configLoaded) { ?>
                        <span class="material-icons" style="color: limegreen">check</span>
                        <?php } else { ?>
                        <span class="material-icons" style="color: red">clear</span>
                        <?php } ?>
                    </li>
                    <li>
                        <span>Created admin user</span>
                        <?php if (count($adminUsers) > 0) { ?>
                        <span class="material-icons" style="color: limegreen">check</span>
                        <?php } else { ?>
                        <span class="material-icons" style="color: red">clear</span>
                        <?php } ?>
                    </li>
                </ul>

                <input type="submit" value="Save settings" />
                <?php if (
                    $baseConfigLoaded &&
                    $imageStoreWritable &&
                    $databaseConnected &&
                    $configLoaded &&
                    count($adminUsers) > 0
                ) { ?>
                <a href="setup.php?complete" class="button">Complete setup &DoubleRightArrow;</a>
                <?php } ?>
            </section>
        </form>

        <?php if ($configLoaded && $databaseConnected) { ?>
        <form action="setup.php?addUser" method="post">
            <section>
                <h2>
                    <span class="material-icons">shield</span>
                    <span>Admin Accounts</span>
                </h2>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Last updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adminUsers as $user) { ?>
                        <tr>
                            <td><?= $user->id ?></td>
                            <td><?= $user->email ?></td>
                            <td><?= $user->name ?></td>
                            <td><?= date(
                                'd.m.Y H:i:s',
                                $user->lastUpdated
                            ) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div class="field">
                    <label for="user-email">
                        <span>Email</span>
                    </label>
                    <input type="text" placeholder="john@example.com" name="user-email" id="user-email">
                </div>
                <div class="field">
                    <label for="user-name">
                        <span>Name</span>
                    </label>
                    <input type="text" placeholder="John Doe" name="user-name" id="user-name">
                </div>
                <div class="field">
                    <label for="user-password">
                        <span>Password</span>
                    </label>
                    <input type="password" placeholder="password" name="user-password" id="user-password">
                </div>

                <?php if ($createUserError) { ?>
                <p class="warn"><?= $createUserError ?></p>
                <?php } ?>

                <input type="submit" value="Add admin">
            </section>
        </form>
        <?php } ?>
    </main>
</body>
</html>