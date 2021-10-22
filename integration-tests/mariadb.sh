#!/usr/bin/env bash

set -e

rm -rf mariadb/data/*
touch mariadb/data/.keep
mysql_install_db --defaults-file="mariadb/my.cnf" --datadir="$PWD/mariadb/data"
mysqld --defaults-file="mariadb/my.cnf" -h "$PWD/mariadb/data"
