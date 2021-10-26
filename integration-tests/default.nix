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
      version = getEnv "WORDPRESS_VERSION" "5.8.1";
      src = fetchurl {
        url = "https://wordpress.org/wordpress-${version}.tar.gz";
        sha256 = getEnv "WORDPRESS_SHA256" "0al2z9jcxgdyq3177q1wk1gxl35j26qmqfvllk4aszd3mz291jlh";
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
