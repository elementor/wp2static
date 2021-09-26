let
  nixpkgs = import <nixpkgs> {};
  inherit (nixpkgs) stdenv fetchurl;
  sources = import ./nix/sources.nix { };
  pkgs = import sources.nixpkgs { };
  wordpress = pkgs.wordpress.overrideAttrs( oldAttrs: rec {
    version = "5.8.1";
    src = fetchurl {
      url = "https://wordpress.org/wordpress-${version}.tar.gz";
      sha256 = "90ca90c4afa37dadc8a4743b5cb111b20cda5f983ce073c2c0bebdce64fa822a";
    };
  });
in
pkgs.mkShell {
  buildInputs = [
    pkgs.mariadb
    pkgs.nginx
    pkgs.php
    pkgs.php74Packages.composer
    pkgs.wp-cli
    wordpress
  ];
}
