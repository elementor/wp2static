# WP2Static Integration Tests

Tests for WP2Static's behavior with a real web server and database.

## Installation

You will need to set up [Nix](https://nixos.org/learn.html) and [direnv](https://direnv.net/docs/installation.html). Then restart your shell and run `direnv allow` from this directory. Nix will install the dependencies.

Optional:
* [nix-direnv](https://github.com/nix-community/nix-direnv) is much faster than the default, but it takes a little extra setup.
* [envrc.el](https://github.com/purcell/envrc) provides fast direnv integration for emacs.

You will need to have the wp2static repo at ../wp2static/. The zip will be built and installed in WordPress automatically. You can set WP2STATIC_PATH to use a different directory.

## Usage

`nix-shell --run "clojure -X:test"`

## Test system

 - NGINX on localhost:7000
 - MariaDB on mariadb/data/mysql.sock
 - PHP-FPM on php/fpm.sock
 - WordPress