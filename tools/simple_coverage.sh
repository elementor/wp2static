#!/bin/bash

# Report classes without matching unit tests

# run from project root
EXEC_DIR=$(pwd)

find ./tests/unit/ -name "*Test.php" | wc -l

echo "Unit tests"
echo ""

find ./src/ -name "*.php" | wc -l

echo "Classes"
