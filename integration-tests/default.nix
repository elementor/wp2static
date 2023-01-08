{ sources ? import ../nix/sources.nix, pkgs ? import sources.nixpkgs { } }:
let inherit (pkgs) fetchurl;
in with pkgs;
let
  getEnv = name: default:
    (if "" == builtins.getEnv name then default else builtins.getEnv name);
  composerPackages = {
    "8.0" = php80Packages.composer;
    "8.1" = php81Packages.composer;
    "8.2" = php82Packages.composer;
  };
  phpPackages = {
    "8.0" = pkgs.php80;
    "8.1" = pkgs.php81;
    "8.2" = pkgs.php82;
  };
  phpVersion = getEnv "PHP_VERSION" "8.0";
  composer = lib.getAttr phpVersion composerPackages;
  php = lib.getAttr phpVersion phpPackages;
  wordpress = (pkgs.wordpress.overrideAttrs (oldAttrs: rec {
    version = getEnv "WORDPRESS_VERSION" "6.1.1";
    src = fetchurl {
      url = "https://wordpress.org/wordpress-${version}.tar.gz";
      sha256 = getEnv "WORDPRESS_SHA256"
        "sha256-IR6FSmm3Pd8cCHNQTH1oIaLYsEP1obVjr0bDJkD7H60=";
    };
  }));
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
