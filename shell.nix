{
  pkgs ? import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/refs/heads/master.tar.gz") {},
}:

pkgs.mkShell {
  buildInputs = with pkgs; [
    traefik
    gettext
  ];
}
