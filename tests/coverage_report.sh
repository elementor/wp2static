#!/bin/sh

# Report classes without matching unit tests

find ./tests/unit/ -name "*Test.php" -exec basename {} \; | sed 's/Test//g' | sort > /tmp/unittests

find ./src/ -name "*.php" -exec basename {} \; | sort > /tmp/classes

printf "\\n\\nFollowing classes are missing unit tests:\\n\\n"

# diff --suppress-common-lines /tmp/unittests /tmp/classes | sed 's/> //g' | grep php
diff /tmp/unittests /tmp/classes | sed 's/> //g' | grep php

printf "\\n\\n"
