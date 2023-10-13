{
  pkgs ? import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/refs/heads/master.tar.gz") {},
}:
let
    php_custom = pkgs.php82.buildEnv {
      extensions = { all, enabled }: with all; enabled ++ [ xdebug xsl ];
      extraConfig = ''
        memory_limit=-1
        xdebug.mode=debug
      '';
    };

    PROJECT_ROOT = builtins.getEnv "PWD";

    rooter = pkgs.writeShellScriptBin "rooter" ''
        export ROOTER_TRAEFIK_BIN=${pkgs.traefik}/bin/traefik
        export ROOTER_DNSMASQ_BIN=${pkgs.dnsmasq}/bin/dnsmasq
        export ROOTER_GZIP_BIN=${pkgs.gzip}/bin/gzip
        export ROOTER_PV_BIN=${pkgs.pv}/bin/pv
        ${php_custom}/bin/php ${PROJECT_ROOT}/rooter.php "$@"
    '';
in
    pkgs.mkShell {
        buildInputs = with pkgs; [
            traefik
            dnsmasq
            pv
            gzip
            php_custom
            php_custom.packages.composer
            rooter
        ];
        shellHook = ''
            ln -sf ${rooter}/bin/rooter ./rooter

            ${rooter}/bin/rooter init
        '';
    }
