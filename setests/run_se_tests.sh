#!/bin/bash

php composer.phar install

./vendor/bin/phpunit --bootstrap vendor/autoload.php test.php
