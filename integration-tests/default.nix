{ sources ? import ../nix/sources.nix, pkgs ? import sources.nixpkgs { } }:
let
  inherit (pkgs) fetchurl;
in with pkgs;
let
  getEnv = name: default: (
    if "" == builtins.getEnv name
    then default
    else builtins.getEnv name
  );
  composerPackages = {
    "7.4" = php74Packages.composer;
    "8.0" = php80Packages.composer;
    "8.1" = php81Packages.composer;
  };
  phpPackages = {
    "7.4" = pkgs.php74;
    "8.0" = pkgs.php80;
    "8.1" = pkgs.php81;
  };
  phpVersion = getEnv "PHP_VERSION" "7.4";
  composer = lib.getAttr phpVersion composerPackages;
  php = lib.getAttr phpVersion phpPackages;
  wordpress = ( pkgs.wordpress.overrideAttrs( oldAttrs: rec {
      version = getEnv "WORDPRESS_VERSION" "5.9.2";
      src = fetchurl {
        url = "https://wordpress.org/wordpress-${version}.tar.gz";
        sha256 = getEnv "WORDPRESS_SHA256" "12wzqrh21sh6pgvs0ayabcv66psq9a8cfz3qk43r5kjn5bfz4rbp";
      };
    })
  );
in with pkgs; [
  (clojure.override { jdk = jdk17_headless; })
  composer
  git
  mariadb
  nginx
  php
  rlwrap
  unzip
  wordpress
  wp-cli
  zip
]
