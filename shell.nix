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
            dnsmasq
            pv
            gzip
            php_custom
            php_custom.packages.composer
            rooter
        ];
        shellHook = ''
            rm ./rooter
            ln -s ${rooter}/bin/rooter.sh ./rooter

            # init links to bin in ROOTER_HOME_DIR
            mkdir -p $HOME/.rooter/bin
            rm $HOME/.rooter/bin/traefik; ln -s `which traefik` $HOME/.rooter/bin/traefik
            rm $HOME/.rooter/bin/dnsmasq; ln -s `which dnsmasq` $HOME/.rooter/bin/dnsmasq
            rm $HOME/.rooter/bin/pv; ln -s `which pv` $HOME/.rooter/bin/pv
            rm $HOME/.rooter/bin/gzip; ln -s `which gzip` $HOME/.rooter/bin/gzip
        '';
    }
