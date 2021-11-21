#!/usr/bin/env bash

set -ouex

cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"

for PHP_VERSION in "7.4" "8.0"; do
    # shellcheck disable=SC2043
    for WP in "5.8.2=1zzj8bhg9pxv2sfqssx7bc41ba4z6pm2hxpnddm7nk2pcr79xlm3"; do
        WORDPRESS_VERSION="${WP%=*}"
        WORDPRESS_SHA256="${WP#*=}"
        echo "PHP Version $PHP_VERSION, Wordpress Version $WORDPRESS_VERSION"
        PHP_VERSION=$PHP_VERSION WORDPRESS_SHA256=$WORDPRESS_SHA256 WORDPRESS_VERSION=$WORDPRESS_VERSION nix-shell --run "clojure -X:test"
    done
done
