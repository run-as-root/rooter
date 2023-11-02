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
      rooterVersion = (builtins.readFile ./VERSION);
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
          rooterPhar = builtins.fetchurl {
            url = "https://github.com/run-as-root/rooter/releases/download/${rooterVersion}/rooter.phar";
            sha256 = "1v05frbzja72fz9bbqb52frs32p4dd47jjf6fiij44v7azx1pbny";
          };
        in
          pkgs.writeScriptBin "rooter" ''
            #!${pkgs.stdenv.shell}
            export ROOTER_APP_MODE=production
            export ROOTER_VERSION=${rooterVersion}
            ${envConfig}
            ${php}/bin/php ${rooterPhar} "$@"
          '';

      packages.rooterDev =
        let
          inherit (pkgs) stdenv lib;
          PROJECT_ROOT = builtins.getEnv "PWD";
        in
          pkgs.writeShellScriptBin "rooterDev" ''
            export ROOTER_APP_MODE=develop
            export ROOTER_VERSION="develop@${rooterVersion}"
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
                install -t $out/bin build/output/rooter.phar;
              '';
          };
        in
          pkgs.writeShellScriptBin "rooterDevPhar" ''
              export ROOTER_APP_MODE=develop
              export ROOTER_VERSION="develop-phar@${rooterVersion}"
              ${envConfig}
              ${phpDev}/bin/php ${rooterPharLocal}/bin/rooter.phar "$@"
          '';

      packages.default = self.packages.${system}.rooter;

      devShells.default = pkgs.mkShell {
        buildInputs = with pkgs; [phpDev traefik dnsmasq pv gzip packages.rooterDev];
      };
    });
}
