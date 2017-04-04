#!/bin/bash

set -e

DIR=`dirname $0`

HERE=`dirname $0`
ROOT="$HERE/.."

# Installation
# Unit tests setup
$HERE/install-wp-test.sh wordpress_test root '' 127.0.0.1 latest

# PHP code sniffer setup
composer install
$ROOT/bin/phpcs --config-set installed_paths $ROOT/vendor/wp-coding-standards/wpcs

set +e

# Check PHP code style
/bin/bash $DIR/phpcodesniffer.sh
phpcs_exit=$?
echo "PHP code style exit code: $phpcs_exit"

# make sure there is a valid WP config around
cp $ROOT/env_config/dev/wp-functest-config.php $DIR/../web/wp-functest-config.php

# run unit tests
/bin/bash $DIR/unit-tests.sh
unit_exit=$?
echo "PHPUnit (unit tests) exit code: $unit_exit"

echo ""
if [ $phpcs_exit -eq 0 -a $unit_exit -eq 0 ] ; then
    echo "======= SUMMARY ======="
    echo "PHP code sniffer: success"
    echo "PHPUnit tests (unit tests): success"
    echo "Everything okay!"
    echo "======================="
    exit 0
else
    echo "======= SUMMARY ======="
    if [ $phpcs_exit -eq 0 ] ; then
        echo "PHP code sniffer: success"
    else
        echo "PHP code sniffer: failed"
    fi
    if [ $unit_exit -eq 0 ] ; then
        echo "PHPUnit tests (unit tests): success"
    else
        echo "PHPUnit tests (unit tests): failed"
    fi
    echo "Something went wrong, see output above"
    echo "======================="
    exit 1
fi
