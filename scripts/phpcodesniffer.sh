#!/bin/sh

DIR=`dirname $0`

files_to_check=$1
if [ -z "$1" ]; then
    files_to_check="${DIR}/src"
fi

$DIR/../bin/phpcs -p --report-width=100 "$files_to_check"
exit $?
