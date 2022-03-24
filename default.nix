let
  rev = "3eb07eeafb52bcbf02ce800f032f18d666a9498d";
  nixpkgs = fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${rev}.tar.gz";
    sha256 = "1ah1fvll0z3w5ykzc6pabqr7mpbnbl1i3vhmns6k67a4y7w0ihrr";
  };
  pkgs = import nixpkgs {};
in with pkgs;
mkShell {
  buildInputs = [
    php
    phpPackages.composer
    shellcheck
    zip
  ];
}
