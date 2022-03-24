#!/usr/bin/env bash

set -e

WP2STATIC_SYMLINK=t nix-shell --run "clojure -M:dev-server"
