#!/bin/sh

set -e

DIR=`dirname $0`

set +e

$DIR/../bin/phpunit
exit $?
