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
* WordPress >= 4.0
* PHP >= 5.6

### Installation

1. Clone this repo

1. Install composer dependencies

    ```
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    ```

1. Install Node dependencies

    ```
    $ npm install
    ```

### Compile assets

    $ npm run build

### Extract messages / Compile translation files

Run the following script to extract messages from PHP-files and generate a new rokka-wordpress-plugin.pot file:

    $ scripts/translations/extract_messages.sh

Update all .po files with newly extracted messages from .pot file:

    $ scripts/translations/update_translation_files.sh

To compile all .po files to .mo files use the following script:

    $ scripts/translations/compile_translation_files.sh

### Unit tests

To run the unit tests you need to setup your local WordPress testing environment (PHP / MySQL required). Use the following script for this:

    $ scripts/init-unit-test-environment.sh

To run the tests use the following script:

    $ php composer.phar test

### Code Sniffer

Execute the code sniffer by executing the following command from the plugin root:

    $ php composer.phar lint

Fix the errors in prior to commit. Commit when fixed, so the build will pass on [Travis CI](https://travis-ci.org/rokka-io/rokka-wordpress-plugin).

## Release new plugin version

To release a new version to the WordPress plugin repository use the following script:

    $ scripts/deploy-wp-plugin.sh

This command will automatically release the latest git tag as a version in the plugin repository.
