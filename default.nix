let
  rev = "5f244caea76105b63d826911b2a1563d33ff1cdc";
  nixpkgs = fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${rev}.tar.gz";
    sha256 = "1xlgynfw9svy7nvh9nkxsxdzncv9hg99gbvbwv3gmrhmzc3sar75";
  };
  pkgs = import nixpkgs {};
  inherit (pkgs) fetchurl;
  wordpress = pkgs.wordpress.overrideAttrs( oldAttrs: rec {
    version = "5.8.1";
    src = fetchurl {
      url = "https://wordpress.org/wordpress-${version}.tar.gz";
      sha256 = "0al2z9jcxgdyq3177q1wk1gxl35j26qmqfvllk4aszd3mz291jlh";
    };
  });
in with pkgs; [
  (pkgs.clojure.override { jdk = openjdk11_headless; })
  git
  mariadb
  nginx
  php
  php74Packages.composer
  rlwrap
  unzip
  wordpress
  wp-cli
  zip
]
