<p align="center">
    <img alt="Cookbook" src="client/src/assets/images/cookbook.svg" width="150"/>
</p>
<h1 align="center">Cookbook - Self-hosted recipes</h1>

<p align="center">
<img alt="GitHub" src="https://img.shields.io/github/license/m-thalmann/cookbook">
<img alt="GitHub code size in bytes" src="https://img.shields.io/github/languages/code-size/m-thalmann/cookbook">
<img alt="GitHub issues" src="https://img.shields.io/github/issues/m-thalmann/cookbook">
<a href="https://codecov.io/gh/m-thalmann/Cookbook">
   <img src="https://codecov.io/gh/m-thalmann/Cookbook/branch/master/graph/badge.svg?token=KO36KNI37E"/>
</a>
</p>

## Description

Cookbook is a web application that stores all your favorite recipes. Self-hosting gives you full control over your personal recipes and lets you share them with your friends and family.

![Cookbook](docs/images/cookbook.png)

## Selfhosting

### Requirements

- PHP ^8.1
- Composer (if you want to execute certain commands on the server)
- MySQL (or almost any other SQL-Server)
- Apache Webserver
  - Alternatively any other webserver, in which case you have to replicate the behavior of the `.htaccess` files in `api/public` and `client/src/.htaccess`
  - Make sure you enable the `mod-rewrite` for Apache and set `AllowOverride All` in order for the `.htaccess` files to work.

To check the PHP requirements run the following command inside of the release folder (or the api folder):

```
composer check-platform-reqs
```

### From release

1. Download the `Cookbook-<version>.zip` file from the release
1. Create a MySQL-database (alternatively a SQLite-database can be used; see [Development](#development))
1. Host the `public`-directory publicly on an Apache-Webserver
1. Update the `.env` and `public/app/assets/config.json` configuration files if necessary (see [Configuration](#configuration))
1. Run the following command to set the application key: `php artisan key:generate`
   - Alternatively you can set the `APP_KEY` manually inside of the `.env` file
1. Run the following command to setup the database: `php artisan migrate --seed`
   - This also creates the administrator user (you can then update the details of the user)
1. Link the `public` storage: `php artisan storage:link`
   - See: https://laravel.com/docs/10.x/filesystem#the-public-disk
1. Run the following commands (optional; for better performance):
   1. `php artisan cache:clear`
   1. `php artisan route:cache` &rarr; https://laravel.com/docs/10.x/routing#route-caching
   1. `php artisan config:cache` &rarr; https://laravel.com/docs/10.x/configuration#configuration-caching
   1. `php artisan view:cache` &rarr; https://laravel.com/docs/10.x/views#optimizing-views
   - **Info:** If the the config (`.env`) or the routes are updated, the corresponding commands have to be re-executed

> **Info:** The `php artisan <...>` commands can also be executed locally, before uploading the application to the server

### From repository

1. Clone the repository / download and extract it
1. Create a MySQL-database (alternatively a SQLite-database can be used; see [Development](#development))
1. Setup the api:
   1. Navigate to the `api` directory
   1. Install the composer dependencies using `composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-dev`
   1. Copy `.env.example` to `.env` and update the file if necessary (see section [Configuration](#configuration))
   1. Host the `public`-directory publicly on an Apache-Webserver
   1. Run the following command to setup the database: `php artisan migrate --seed`
      - This also creates the administrator user (you can then update the details of the user)
   1. Link the `public` storage: `php artisan storage:link`
   - See: https://laravel.com/docs/10.x/filesystem#the-public-disk
   1. Run the same optional commands from above
1. Setup the client
   1. Navigate into the `client`-directory
   1. Install the npm dependencies using `npm install`
   1. Build the client `npm run build`
   1. Host the created `client/dist/cookbook`-folder publicly
      - Create a configuration file by copying the file `client/dist/Cookbook/assets/config.example.json` to `client/dist/Cookbook/assets/config.json` and setting the values accordingly (see section [Configuration](#configuration))

### Setting up the cronjob

There are some (optional) cleanup commands being executed automatically depending on a cronjob. To set that one up do the following:

`/etc/crontab`:

```crontab
* * * * * www-data cd /project/root && php artisan schedule:run >> /dev/null 2>&1
```

See [Laravel docs](https://laravel.com/docs/9.x/scheduling#running-the-scheduler)

## Configuration

### API configuration

`api/.env`

| Key                              | Datatype  | Description                                                                                          |
| -------------------------------- | --------- | ---------------------------------------------------------------------------------------------------- |
| `APP_ENV`                        | `string`  | The API's environment: production, local, demo                                                       |
| `APP_DEBUG`                      | `boolean` | Whether to pass debug-messages (errors etc.) to the client. <br> Should not be enabled in production |
| `APP_URL`                        | `string`  | The url at which the API is reachable                                                                |
| `APP_FRONTEND_URL`               | `string`  | The url at which the client is reachable                                                             |
| `APP_TIMEZONE`                   | `string`  | The servers timezone                                                                                 |
| `APP_DEFAULT_LANGUAGE`           | `string`  | The default language to use                                                                          |
| `APP_SIGN_UP_ENABLED`            | `boolean` | Whether users are allowed to register                                                                |
| `APP_EMAIL_VERIFICATION_ENABLED` | `boolean` | Whether the email must be verified                                                                   |
| `HCAPTCHA_ENABLED`               | `boolean` | Whether the hCaptcha is enabled (see below)                                                          |
| `HCAPTCHA_SECRET`                | `string`  | The hCaptcha secret (see below)                                                                      |
| `DB_CONNECTION`                  | `string`  | The database connection to use                                                                       |
| `DB_HOST`                        | `string`  | The host of the database                                                                             |
| `DB_PORT`                        | `string`  | The port of the database                                                                             |
| `DB_DATABASE`                    | `string`  | The database name                                                                                    |
| `DB_USERNAME`                    | `string`  | The username to access the database                                                                  |
| `DB_PASSWORD`                    | `string`  | The password to access the database                                                                  |
| `MAIL_MAILER`                    | `string`  | The mailer to use (normally `smtp`)                                                                  |
| `MAIL_HOST`                      | `string`  | The host of the mailer Host                                                                          |
| `MAIL_PORT`                      | `integer` | The port of the mailer                                                                               |
| `MAIL_USERNAME`                  | `integer` | Email username                                                                                       |
| `MAIL_PASSWORD`                  | `integer` | Email password                                                                                       |
| `MAIL_ENCRYPTION`                | `integer` | The encryption used by the mailer                                                                    |
| `MAIL_FROM_ADDRESS`              | `integer` | The senders email                                                                                    |
| `MAIL_FROM_NAME`                 | `integer` | The senders name                                                                                     |

> `hcaptcha`: hCaptcha is used to prevent bots from signing-up. Create a free account here: https://www.hcaptcha.com

### Client configuration

`client/src/assets/config.json`:

```jsonc
{
  "apiUrl": "/api", // the URL at which the API is reachable (if release is used this should not be changed)
  // hcaptcha data (see above)
  "hcaptcha.enabled": true,
  "hcaptcha.siteKey": "<hcaptcha site-key>"
}
```

## Translation

Currently the Webapp is translated to the following languages:

- English
- German

To add a new translation:

1. Client translations:
   1. Add a file to the `client/src/assets/i18n` directory (use the language-code (ISO 639-1 Language Code))
   1. Translate the keys used in the other translation-files
   - **Info:** You can use the [i18n Manager](https://github.com/gilmarsquinelato/i18n-manager) to create the translations (even though the project is archived it works well).
1. API translations
   1. Copy the `api/lang/en` directory to `api/lang/<language code>`
   1. Update the values

## Development

### API

1. Navigate to the `api` directory
1. Install the composer dependencies: `composer install`
1. Copy the `.env.development` to `.env` and set values if necessary (don't set any database settings)
1. Create the `api/database/database.sqlite` file (empty)
1. Set the application key: `php artisan key:generate`
1. Run the following command to setup the database: `php artisan migrate --seed`
   - This also creates the administrator user (you can then update the details of the user)
1. Link the `public` storage: `php artisan storage:link`
   - See: https://laravel.com/docs/10.x/filesystem#the-public-disk
1. Start: `php artisan serve`

### Client

1. Navigate to the `client` directory
1. Install the npm dependencies: `npm install`
1. Copy the `src/assets/config.example.json` to `src/assets/config.json` and set values if necessary (don't set the `apiUrl`)
1. Start: `npm run start`

> **Info:** The local development environment uses a proxy to access the API (see https://angular.io/guide/build#proxying-to-a-backend-server)

### Tools & Routes

The API has the following helper routes:

- http://localhost:8000/api/docs - Access the OpenAPI documentation (also available in production)
- http://localhost:8000/clockwork - Debug tool of any request made to the api (not available in production)
