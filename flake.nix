{
  description = "rooter";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    utils.url = "github:numtide/flake-utils";
  };

  outputs = {
    self,
    utils,
    nixpkgs,
    ...
  } @ inputs:
    utils.lib.eachDefaultSystem (system:
    let
      pkgs = nixpkgs.legacyPackages.${system};
      # @todo adapt PHP version if required
      php = pkgs.php82.buildEnv {
        extensions = { all, enabled }: with all; enabled;
        extraConfig = ''
            memory_limit=-1
        '';
      };
      phpDev = pkgs.php82.buildEnv {
        extensions = { all, enabled }: with all; enabled ++ [ xdebug ];
        extraConfig = ''
            memory_limit=-1
            xdebug.mode=debug
        '';
      };
      envConfig = ''
        export ROOTER_TRAEFIK_BIN=${pkgs.traefik}/bin/traefik
        export ROOTER_DNSMASQ_BIN=${pkgs.dnsmasq}/bin/dnsmasq
        export ROOTER_GZIP_BIN=${pkgs.gzip}/bin/gzip
        export ROOTER_PV_BIN=${pkgs.pv}/bin/pv
        '';
    in rec {
      packages.php = php;
      packages.traefik = pkgs.traefik;
      packages.dnsmasq = pkgs.dnsmasq;
      packages.pv = pkgs.pv;
      packages.gzip = pkgs.gzip;
      packages.rooter =
        let
          inherit (pkgs) stdenv lib;
          magerun = builtins.fetchurl {
            # @todo fetch rooter.phar from github releases
            url = "https://github.com/netz98/n98-magerun2/releases/download/7.2.0/n98-magerun2.phar";
            sha256 = "0z1dkxz69r9r9gf8xm458zysa51f1592iymcp478wjx87i6prvn3";
          };
        in
          pkgs.writeScriptBin "rooter" ''
            #!${pkgs.stdenv.shell}
            ${envConfig}
            ${php}/bin/php ${magerun} "$@"
          '';

      packages.rooterDev =
        let
          inherit (pkgs) stdenv lib;
          PROJECT_ROOT = builtins.getEnv "PWD";
        in
          pkgs.writeShellScriptBin "rooterDev" ''
            ${envConfig}
            ${phpDev}/bin/php ${PROJECT_ROOT}/rooter.php "$@"
          '';

      packages.rooterDevPhar =
        let
          inherit (pkgs) stdenv lib;
          box = builtins.fetchurl {
            url = "https://github.com/box-project/box/releases/download/4.3.8/box.phar";
            sha256 = "061vrxjvmqxy4yyi6j6i28kwl6ixfwhc743b6lw7bjgc4kdkvml3";
          };
          rooterPharLocal = pkgs.stdenv.mkDerivation {
              name = "rooterPharBin";
              src = self;
              buildPhase = "
                ${pkgs.php82Packages.composer}/bin/composer install
                ${phpDev}/bin/php ${box} compile --composer-bin=${pkgs.php82Packages.composer}/bin/composer
              ";
              installPhase = ''
                mkdir -p $out/bin;
                install -t $out/bin rooter.phar;
              '';
          };
        in
          pkgs.writeShellScriptBin "rooterDevPhar" ''
              ${envConfig}
              ${phpDev}/bin/php ${rooterPharLocal}/bin/rooter.phar "$@"
          '';

      defaultPackage = self.packages.${system}.rooter;

      devShell = pkgs.mkShell {
        buildInputs = with pkgs; [phpDev traefik dnsmasq pv gzip packages.rooterDev];
      };
    });
}
