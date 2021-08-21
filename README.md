<p align="center">
    <img alt="Cookbook" src="https://raw.githubusercontent.com/m-thalmann/Cookbook/master/client/src/assets/images/cookbook.svg" width="150"/>
</p>
<h1 align="center">Cookbook - Self-hosted recipes</h1>

<p align="center">
<img alt="GitHub" src="https://img.shields.io/github/license/m-thalmann/cookbook">
<img alt="GitHub code size in bytes" src="https://img.shields.io/github/languages/code-size/m-thalmann/cookbook">
<img alt="GitHub issues" src="https://img.shields.io/github/issues/m-thalmann/cookbook">
</p>

## Description

Cookbook is a web application that stores all your favorite recipes. Self-hosting gives you full control over the precious, secret family recipes and lets you share them with your friends.

## Selfhosting

1. Clone the repository / download and extract it
1. Create a mysql-database and execute the contents of the `api/database/db.sql` file on it
1. Host the `api`-directory publicly on an Apache-Webserver
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - Create a configuration file by copying the file `api/config/config.example.json` to `api/config/config.json` and setting the values accordingly (see section [Configuration](#configuration))
1. Navigate into the `client`-directory and install the npm-dependencies:
   - `npm install`
1. Build the client:
   - `ng build --prod`
1. Host the created `client/dist/Cookbook`-folder publicly
   - Make sure you enabled `AllowOverride All` in order for the `.htaccess` file to work
   - Create a configuration file by copying the file `client/dist/Cookbook/assets/config.example.json` to `client/dist/Cookbook/assets/config.json` and setting the values accordingly (see section [Configuration](#configuration))

### Configuration

#### API configuration

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
  "token": {
    "secret": "<secret>", // secret used to encode the JWT tokens
    "ttl": 604800 // seconds after which a token expires
  },
  "password": {
    "secret": "<secret>", // secret used to hash the passwords
    "reset_ttl": 600 // seconds after which the password-reset-token expires
  },
  "registration_enabled": true, // whether users are allowed to register
  "email_verification": true, // whether the email must be verified
  "hcaptcha": {
    // hcaptcha data (see below)
    "enabled": true,
    "secret": "<hcaptcha secret>"
  },
  "mail": {
    // mail settings
    "smtp": {
      "host": "smtp.example.com",
      "port": "465",
      "encrypted": true,
      "username": "cookbook@example.com",
      "password": "password"
    },
    "from": {
      "mail": "cookbook@example.com",
      "name": "Cookbook"
    }
  }
}
```

**Important**: Make sure to change the secrets to a long, random string

`image_store`: If this value is null, the `api/data` directory is used, otherwise the set path is used. Make sure that the web-user (`www-data`) is allowed to write and read from this directory.

`hcaptcha`: hCaptcha is used to prevent bots from signing-up. Create a free account here: https://www.hcaptcha.com/signup-interstitial

#### Client configuration

```jsonc
{
  "api_url": "http://localhost:80/api", // the URL at which the API is reachable
  "hcaptcha": {
    // hcaptcha data (see above)
    "enabled": true,
    "site_key": "<hcaptcha site-key>"
  }
}
```
