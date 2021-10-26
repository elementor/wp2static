#!/usr/bin/env bash

set -e

nix-shell --run "clojure -M:dev-server"
