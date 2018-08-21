#!/bin/bash

cd setests

# kill selenium containers in case of a failed test, new connections can't be made

sudo docker rm -f {selenium-hub,sechrome,seffox}

# setup a Selenium grid for E2E tests
# https://robotninja.com/blog/introduction-using-selenium-docker-containers-end-end-testing/
sudo docker run -d -p 4444:4444 --name selenium-hub selenium/hub:latest
sudo docker run --name sechrome -d --link selenium-hub:hub selenium/node-chrome:latest
sudo docker run --name seffox -d --link selenium-hub:hub selenium/node-firefox:latest

# wait for segrid to become available
sleep 2

php composer.phar install

./vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php tests.php
