#!/bin/bash

curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
git clone https://github.com/wimg/PHPCompatibility.git
git clone -b master https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git wpcs
php phpcs.phar --config-set installed_paths /home/ubuntu/PHPCompatibility,/home/ubuntu/wpcs
php phpcs.phar --ignore=*/provisioning/*,*.dbus/*,*/PHPCompatibility/*,*/wpcs/*,*/vendor/*,*/node_modules/* --standard=PHPCompatibility,WordPress ./wordpress-static-html-plugin/
