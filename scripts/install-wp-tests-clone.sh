#!/usr/bin/env bash

DB_HOST=$1

mysql --host $DB_HOST --port 3306 -uroot -proot -e "SHOW DATABASES"
mysql --host $DB_HOST --port 3306 --user="root" --password="root" -e "drop database if exists test"
