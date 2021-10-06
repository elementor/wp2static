# WP2Static Integration Tests

Tests for WP2Static's behavior with a real web server and database.

## Installation

You will need to set up [Nix](https://nixos.org/learn.html) and [direnv](https://direnv.net/docs/installation.html). Then restart your shell and run `direnv allow` from this directory. Nix will install the dependencies.

You will need to have the wp2static repo at ../wp2static/. The zip will be built and installed in WordPress automatically.

## Test system

 - NGINX on localhost:7000
 - MariaDB on localhost:7001
 - PHP-FPM on localhost:7002
 - WordPress