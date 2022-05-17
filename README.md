# Rokka integration plugin for WordPress

[![Build Status](https://github.com/rokka-io/rokka-wordpress-plugin/workflows/Lint%20Test%20Deploy/badge.svg?branch=master)](https://github.com/rokka-io/rokka-wordpress-plugin/actions?query=workflow%3A%22Lint+Test+Deploy%22+branch%3Amaster)

WordPress plugin to integrate the [rokka.io](https://rokka.io) image service.

[rokka](https://rokka.io) is digital image processing done right. Store, render and deliver images. Easy and blazingly fast. This Wordpress plugin automatically uploads your pictures to rokka and delivers them in the right format, as light and as fast as possible. And you only pay what you use, no upfront and fixed costs.

Free account plans are available. Just install the plugin, register and use it.

## Further Information

* Documentation: https://github.com/rokka-io/rokka-wordpress-plugin/wiki
* WordPress Plugin: https://wordpress.org/plugins/rokka-integration/
* Website: https://rokka.io
* Changelog: https://github.com/rokka-io/rokka-wordpress-plugin/releases
* GitHub Repository: https://github.com/rokka-io/rokka-wordpress-plugin
* Issue tracker: https://github.com/rokka-io/rokka-wordpress-plugin/issues

## Development

### Requirements

* Node.js >= 16 (https://nodejs.org/)
* gettext (https://www.gnu.org/software/gettext/)
* WordPress >= 4.7
* PHP >= 7.1

### Installation

1. Clone this repo

1. Install composer dependencies

    ```bash
    curl -s https://getcomposer.org/installer | php
    php composer.phar install
    ```

1. Install Node dependencies

    ```bash
    npm install
    ```

### Compile assets

* `npm run dev`: Builds assets in development mode and watches for any changes and reports back any errors in your code.
* `npm run lint`: Lints JavaScript & PHP files.
* `npm run build`: Build production code into `assets/dist` folder.

### Extract messages / Compile translation files

1. To extract the labels and generate the `languages/rokka-integration.pot` file run the following command:

    ```bash
    ./scripts/translations/extract-messages.sh
   ```

2. To update the translation files (`*.po`) run the following command:

    ```bash
    ./scripts/translations/update-translation-files.sh
   ```

1. To generate the `*.mo` translation files run the following command:

   ```bash
   ./scripts/translations/compile-translation-files.sh
   ```

### Setup local dev environment

The following commands can be used to set up a local dev environment. See the official [documentation of `@wordpress/env`](https://developer.wordpress.org/block-editor/packages/packages-env/#command-reference) for a complete list of commands.

* `npm run wp-env start`: Starts the Docker containers.
* `npm run wp-env stop`: Stops the Docker containers.

### Unit tests

To run the tests use the following command:

```bash
npm run test:unit:php
```

or the following command to run a specific test:

```bash
npm run test:unit:php -- --filter 'my_test'
```

## Release new plugin version

To release a new version to the WordPress plugin repository use the following script:

```bash
./scripts/deploy-wp-plugin.sh
```

This command will automatically release the latest git tag as a version in the plugin repository.
