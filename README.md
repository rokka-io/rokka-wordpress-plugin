# rokka-wordpress-plugin

WordPress plugin to integrate Rokka.io image service

## Development

### Requirements

* Node.js >=0.12.x (https://nodejs.org/)

    ```
    $ npm install -g gulp
    ```

### Installation

1. Clone this repo

1. Install composer dependencies

    ```
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install
    $ bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs
    ```

1. Install Node dependencies

    ```
    $ npm install
    ```

## Compile assets

    $ gulp deploy

## Extract messages / Compile translation files

Run the following script to extract messages from PHP-files and generate a new nebis.pot file:

    $ scripts/translations/extract_messages.sh

Update all .po files with newly extracted messages from .pot file:

    $ scripts/translations/update_translation_files.sh

To compile all .po files to .mo files use the following script:

    $ scripts/translations/compile_translation_files.sh