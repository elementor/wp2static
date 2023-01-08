#!/usr/bin/env bash

set -uex

cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"

for PHP_VERSION in "8.0" "8.1" "8.2"; do
    # shellcheck disable=SC2043
    for WP in "6.1.1:sha256-IR6FSmm3Pd8cCHNQTH1oIaLYsEP1obVjr0bDJkD7H60="; do
        WORDPRESS_VERSION="${WP%:*}"
        WORDPRESS_SHA256="${WP#*:}"
        echo "PHP Version $PHP_VERSION, Wordpress Version $WORDPRESS_VERSION"
        PHP_VERSION=$PHP_VERSION WORDPRESS_SHA256=$WORDPRESS_SHA256 WORDPRESS_VERSION=$WORDPRESS_VERSION nix-shell --run "clojure -X:test"
    done
done
