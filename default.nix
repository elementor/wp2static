{ sources ? import ./nix/sources.nix, pkgs ? import sources.nixpkgs { } }:
with pkgs;
mkShell {
  buildInputs = [
    php
    phpPackages.composer
    shellcheck
    zip
  ];
}
