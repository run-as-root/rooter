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

    rooter = pkgs.writeShellScriptBin "rooter.sh" ''
        ${php_custom}/bin/php ${PROJECT_ROOT}/rooter.php "$@"
    '';
in
    pkgs.mkShell {
        buildInputs = with pkgs; [
            traefik
            gettext
            php_custom
            php_custom.packages.composer
            rooter
        ];
        shellHook = ''
            echo ${PROJECT_ROOT}
            rm ./rooter
            ln -s ${rooter}/bin/rooter.sh ./rooter
        '';
    }
