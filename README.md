<p align="center">
    <img alt="Cookbook" src="client/src/assets/images/cookbook.svg" width="150"/>
</p>
<h1 align="center">Cookbook - Self-hosted recipes</h1>

<p align="center">
<img alt="GitHub" src="https://img.shields.io/github/license/m-thalmann/cookbook">
<img alt="GitHub code size in bytes" src="https://img.shields.io/github/languages/code-size/m-thalmann/cookbook">
<img alt="GitHub issues" src="https://img.shields.io/github/issues/m-thalmann/cookbook">
</p>

## Description

Cookbook is a web application that stores all your favorite recipes. Self-hosting gives you full control over the precious, secret family recipes and lets you share them with your friends.

![Cookbook](docs/images/cookbook.png)

## Selfhosting

### From release

1. Download the `Cookbook-<version>.zip` file from the release
1. Create a mysql-database
1. Host the `api`-directory publicly on an Apache-Webserver
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - In your browser navigate to `<API-URL>/setup.php` to run the setup (see section [API-Setup](#api-setup))
1. Host the `client`-directory publicly
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - Create a configuration file by copying the file `config.example.json` to `config.json` and setting the values accordingly (see section [Configuration](#configuration))

### From repository

1. Clone the repository / download and extract it
1. Create a mysql-database
1. Host the `api`-directory publicly on an Apache-Webserver
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - In your browser navigate to `<API-URL>/setup.php` to run the setup (see section [API-Setup](#api-setup))
1. Navigate into the `client`-directory and install the npm-dependencies:
   - `npm install`
1. Build the client:
   - `ng build --prod`
1. Host the created `client/dist/Cookbook`-folder publicly
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - Create a configuration file by copying the file `client/dist/Cookbook/assets/config.example.json` to `client/dist/Cookbook/assets/config.json` and setting the values accordingly (see section [Configuration](#configuration))

## API-Setup

The API-Setup-Script (`<API-URL>/setup.php`) is used to setup the configuration of the API, without the need to configure everything yourself. You can still do it yourself:

1. Create a configuration file by copying the file `api/config/config.example.json` to `api/config/config.json` and setting the values accordingly (see section [Configuration](#configuration))
1. Create a configuration-secret file: `api/config/config_secret` with a random-string secret (see section [Configuration](#configuration))
1. Execute the contents of the `api/database/db.sql` file on the mysql-database

## Configuration

### API configuration

`api/config/config.json` (base-config):

```jsonc
{
  "root_url": "/api", // the URL-Suffix at which the API is reachable
  "database": {
    // database settings
    "host": "localhost",
    "user": "root",
    "password": "123456789",
    "database": "database_name",
    "charset": "utf8"
  },
  "image_store": null, // the directory where the uploaded images are stored (see below)
  "setup_complete": true // OPTIONAL: whether the setup was completed or not (<ROOT_URL>/setup.php)
}
```

`image_store`: If this value is null, the `api/data` directory is used, otherwise the set path is used. Make sure that the web-user (`www-data`) is allowed to write and read from this directory.

The rest of the configuration is stored in the database-table `config`:

| Key                          | Datatype          | Description                                                                                        |
| ---------------------------- | ----------------- | -------------------------------------------------------------------------------------------------- |
| `token.secret`               | `string`          | Secret used to encode the JWT tokens (**Attention: see below**)                                    |
| `token.ttl`                  | `integer` (>= 60) | Seconds after which a token expires                                                                |
| `password.secret`            | `string`          | Secret used to hash the passwords (**Attention: see below**)                                       |
| `password.reset_ttl`         | `integer` (>= 60) | Seconds after which the password-reset-token expires                                               |
| `bad_authentication_limit`   | `integer` (>= -1) | Amount of unsuccessful authentications after which to show an hCaptcha (if enabled). -1 to disable |
| `registration_enabled`       | `boolean`         | Whether users are allowed to register                                                              |
| `email_verification.enabled` | `boolean`         | Whether the email must be verified                                                                 |
| `email_verification.ttl`     | `integer` (>= 60) | Seconds after which the email-verification-token expires                                           |
| `hcaptcha.enabled`           | `boolean`         | Whether the hCaptcha is enabled (see below)                                                        |
| `hcpatcha.secret`            | `string`          | The hCaptcha secret (see below)                                                                    |
| `mail.enabled`               | `boolean`         | Whether emails are enabled or not                                                                  |
| `mail.smtp.host`             | `string`          | SMTP Host                                                                                          |
| `mail.smtp.port`             | `integer`         | SMTP Port                                                                                          |
| `mail.smtp.encrypted`        | `boolean`         | Whether the SMTP connection is encrypted                                                           |
| `mail.smtp.username`         | `string`          | SMTP Username                                                                                      |
| `mail.from.mail`             | `string` (email)  | The sender-email                                                                                   |
| `mail.from.name`             | `string`          | The senders name                                                                                   |

**Important**: Make sure to change the secrets to a long, random string

**Attention:**

- If the `token.secret` is changed, all users will be forcefully logged out
- If the `password.secret` is changed, the passwords in the database are no longer valid and all users need to reset their password

`hcaptcha`: hCaptcha is used to prevent bots from signing-up. Create a free account here: https://www.hcaptcha.com/signup-interstitial

#### API configuration secret

The file `api/config/config_secret` contains a secret to encrypt sensitive configuration-values that are saved in the database. The file needs to be created.

### Client configuration

`client/src/assets/config.json`:

```jsonc
{
  "api_url": "http://localhost:80/api", // the URL at which the API is reachable
  "language": "en", // the default language (if not overwritten by user)
  "hcaptcha": {
    // hcaptcha data (see above)
    "enabled": true,
    "site_key": "<hcaptcha site-key>"
  }
}
```

## Translation

Currently the Webapp is translated to the following languages:

- English
- German

To add a new translation:

1. Add a file to the `client/src/assets/i18n` directory (use the language-code (ISO 639-1 Language Code))
1. Translate the keys used in the other translation-files
1. Add an icon-file to the `client/src/assets/i18n/flag_icons` directory (see https://flagicons.lipis.dev/)
1. Register the new language in the `_languages.json`-file:

   ```jsonc
   [
     // ...
     {
       "key": "en", // the name of the json-file
       "name": "English", // the name to display
       "flagIcon": "gb.svg" // the icon-file to use
     }
     // ...
   ]
   ```

You can use the [i18n Manager](https://github.com/gilmarsquinelato/i18n-manager) to create the translations (even though the project is archived it works well).

## Project structure

### General overview

```
/
│
└─ client - Contains the Angular frontend project
│
└─ api    - Contains the PHP REST API
│
└─ docs   - Contains documents, documentation and images (screenshots) for the project
   │
   └─ api    - Contains the OpenAPI documentation (viewable by e.g. Swagger or Postman)
   │
   └─ images - Contains some screenshots and other images
```

### API structure

```
/api
│
└─ auth - Contains the Authorization-class used for login etc.
│
└─ config - Contains the Config-class, ConfigSettings-class and the config.json and config_secret
│
└─ data/image_store - Directory used to store the images (if not defined otherwise)
│
└─ database - Contains the databases-sql file that defines the tables
│
└─ inc - Contains helper-classes
│
└─ lib - Contains libraries
│
└─ models - Models to interact with the database
│
└─ routes - Contains the routes of the REST-API
│
└─ templates - Contains HTML-templates
```

### Client structure

```
/client/src
│
└─ app
│  │
│  └─ components - Contains shared components
│  │
│  └─ core - Contains services, pipes and other helper classes/files
│  │
│  └─ layout - Contains the layout components
│  │
│  └─ pages - Contains the client's-pages
│
└─ assets - Contains translations, images and the config-file
│  │
│  └─ i18n - Contains translation-files
│  │
│  └─ images - Contains the images
│
└─ styles - Contains the default-styles
```
