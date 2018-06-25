#!/bin/bash
shopt -s globstar
for f in **/*.php; do
  php -l "${f%.*}.php" 1> /dev/null
done
