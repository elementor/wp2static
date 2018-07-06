#!/bin/bash

cd setests

php composer.phar install

./vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php test.php
