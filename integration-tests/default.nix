let
  rev = "5f244caea76105b63d826911b2a1563d33ff1cdc";
  nixpkgs = fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${rev}.tar.gz";
    sha256 = "1xlgynfw9svy7nvh9nkxsxdzncv9hg99gbvbwv3gmrhmzc3sar75";
  };
  pkgs = import nixpkgs {};
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
  };
  phpPackages = {
    "7.4" = pkgs.php;
    "8.0" = pkgs.php80;
  };
  phpVersion = getEnv "PHP_VERSION" "7.4";
  composer = lib.getAttr phpVersion composerPackages;
  php = lib.getAttr phpVersion phpPackages;
  wordpress = ( pkgs.wordpress.overrideAttrs( oldAttrs: rec {
      version = getEnv "WORDPRESS_VERSION" "5.8.2";
      src = fetchurl {
        url = "https://wordpress.org/wordpress-${version}.tar.gz";
        sha256 = getEnv "WORDPRESS_SHA256" "1zzj8bhg9pxv2sfqssx7bc41ba4z6pm2hxpnddm7nk2pcr79xlm3";
      };
    })
  );
in with pkgs; [
  (clojure.override { jdk = openjdk11_headless; })
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
