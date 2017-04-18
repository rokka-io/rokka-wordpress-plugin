#!/bin/bash

set -e
HERE=`dirname $0`
ROOT="$HERE/.."

$ROOT/wp-cli.phar core config --dbname=wordpress_test --dbuser=rokkavm --dbpass=123 --dbhost=127.0.0.1 --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar core install --url=http://siteurl.com --title=SiteTitle --admin_user=username --admin_password=mypassword --admin_email=my@email.com --path="$WP_CORE_DIR"

echo "Enabling WordPress plugins..."
$ROOT/wp-cli.phar plugin activate rokka-wordpress-plugin --path="$WP_CORE_DIR"

echo "Setting WordPress options..."
$ROOT/wp-cli.phar option add rokka_company_name 'rokka_test' --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar option add rokka_api_key '123' --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar option add rokka_api_secret 'asdf' --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar option add rokka_rokka_enabled 'on' --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar option add rokka_output_parsing '' --path="$WP_CORE_DIR"
$ROOT/wp-cli.phar option add rokka_stack_prefix 'wp-rokka-' --path="$WP_CORE_DIR"
