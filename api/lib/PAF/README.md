# PAF

PAF (PHP API Framework) is a framework for creating API's through PHP and outputting them as JSON (also other formats are possible).

This file explains the main concepts and functions of PAF. If you want a more in-depth documentation, you find the phpDocumentator documentation of all classes here: https://m-thalmann.github.io/PAF

## Table of contents

-   [Setting up](#setting-up)
-   [Quick start](#quick-start)
-   [Components](#components)
-   [Contributing](#contributing)

## Setting up

1. Download this repository and copy the contents of the `src/` folder into (for example) your `lib/PAF` folder
2. Create a `index.php` file and require the `lib/PAF/autoload.php` file. This will automatically load the needed classes. This will **not** interfere with your own/other autoloaders!
    - **Alternatively:** Require each file you need separately
3. If you want to use the `PAF\Router`, you should also follow step 2 of its setup-guide
4. Now you are ready to go

<hr>

## Quick start

```php
<?php

require_once 'path/to/autoload.php';

// start using PAF
use PAF\Router\Router;

Router::init();

// ...
```

<hr>

## Components

PAF contains different components for different use-cases:

-   `PAF\Router` - Contains classes for routing and outputting responses (mainly) as json ([README](src/Router), [Documentation](https://m-thalmann.github.io/PAF/namespaces/paf-router.html))
-   `PAF\Model` - Contains classes for mapping database entries to PHP objects ([README](src/Model), [Documentation](https://m-thalmann.github.io/PAF/namespaces/paf-model.html))

## Documentation generation

The documentation gets auto-generated on each push to the master-branch. The resulting documentation is then pushed to the docs branch,
which is then made available through [GitHub-Pages](https://m-thalmann.github.io/PAF).

If you want to generate the documentation for yourself, you have to get the [phpDocumentor](https://www.phpdoc.org/) (v3) by executing the following lines:

```bash
wget -O phpDoc https://phpdoc.org/phpDocumentor.phar
chmod +x phpDoc
```

Then you have to make it available globally (by adding an alias for it or by adding it to the `$PATH` variable) and execute it inside the PAF root like shown:

```bash
phpDoc run --visibility="public,protected"
```

If you also want to include _private_ functions and variables, you can omit the visibility flag.

## Contributing

### Prettier

When contributing please run prettier before commiting to the repository:

1.  Install prettier (with php-plugin): `npm install --global prettier @prettier/plugin-php`
2.  Run prettier: `prettier --write .`
