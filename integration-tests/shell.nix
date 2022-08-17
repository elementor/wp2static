{ sources ? import ../nix/sources.nix, pkgs ? import sources.nixpkgs { } }:
let
  default = import ./default.nix {};
in with pkgs;
mkShell {
  buildInputs = default;

  WORDPRESS_PATH = lib.findFirst (x: (builtins.hasAttr "pname" x) && "wordpress" == x.pname) "" default;
}
