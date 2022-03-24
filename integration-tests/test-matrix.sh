#!/usr/bin/env bash

set -ouex

cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"

for PHP_VERSION in "7.4" "8.0" "8.1"; do
    # shellcheck disable=SC2043
    for WP in "5.9.2=12wzqrh21sh6pgvs0ayabcv66psq9a8cfz3qk43r5kjn5bfz4rbp"; do
        WORDPRESS_VERSION="${WP%=*}"
        WORDPRESS_SHA256="${WP#*=}"
        echo "PHP Version $PHP_VERSION, Wordpress Version $WORDPRESS_VERSION"
        PHP_VERSION=$PHP_VERSION WORDPRESS_SHA256=$WORDPRESS_SHA256 WORDPRESS_VERSION=$WORDPRESS_VERSION nix-shell --run "clojure -X:test"
    done
done
