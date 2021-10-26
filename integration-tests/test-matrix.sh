#!/usr/bin/env bash

set -e

for PHP_VERSION in "7.4" "8.0"; do
    # shellcheck disable=SC2043
    for WP in "5.8=0zz8fy2i0b00nqngcc5cj67hlshj65kgma7j37sih4rnanmi766j"; do
#0al2z9jcxgdyq3177q1wk1gxl35j26qmqfvllk4aszd3mz291jlh"; do
        WORDPRESS_VERSION="${WP%=*}"
        WORDPRESS_SHA256="${WP#*=}"
        echo "PHP Version $PHP_VERSION, Wordpress Version $WORDPRESS_VERSION"
        PHP_VERSION=$PHP_VERSION WORDPRESS_SHA256=$WORDPRESS_SHA256 WORDPRESS_VERSION=$WORDPRESS_VERSION nix-shell --pure --run "clojure -X:test"
    done
done
