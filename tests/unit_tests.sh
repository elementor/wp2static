#!/bin/sh

# set script dir to cwd
cd "$(dirname "$0")"

phpunit --testdox ./HTMLProcessor/
