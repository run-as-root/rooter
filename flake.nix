{
  description = "rooter";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    nixpkgs-stable.url = "github:NixOS/nixpkgs/nixos-25.11";
    utils.url = "github:numtide/flake-utils";
  };

  outputs = {
    self,
    utils,
    nixpkgs,
    nixpkgs-stable,
    ...
  } @ inputs:
    utils.lib.eachDefaultSystem (system:
    let
      pkgs = nixpkgs.legacyPackages.${system};
      pkgs-stable = nixpkgs-stable.legacyPackages.${system};
      rooterVersion = (builtins.readFile ./VERSION);
      # @todo adapt PHP version if required
      php = pkgs.php84.buildEnv {
        extensions = { all, enabled }: with all; enabled;
        extraConfig = ''
            memory_limit=-1
        '';
      };
      phpDev = pkgs.php84.buildEnv {
        extensions = { all, enabled }: with all; enabled ++ [ xdebug ];
        extraConfig = ''
            memory_limit=-1
            xdebug.mode=debug
        '';
      };
      envConfig = ''
        export ROOTER_TRAEFIK_BIN=${pkgs-stable.traefik}/bin/traefik
        export ROOTER_DNSMASQ_BIN=${pkgs.dnsmasq}/bin/dnsmasq
        export ROOTER_GZIP_BIN=${pkgs.gzip}/bin/gzip
        export ROOTER_PV_BIN=${pkgs.pv}/bin/pv
        '';
    in rec {
      packages.php = php;
      packages.traefik = pkgs-stable.traefik;
      packages.dnsmasq = pkgs.dnsmasq;
      packages.pv = pkgs.pv;
      packages.gzip = pkgs.gzip;
      packages.rooter =
        let
          inherit (pkgs) stdenv lib;
          rooterPhar = builtins.fetchurl {
            url = "https://github.com/run-as-root/rooter/releases/download/${rooterVersion}/rooter.phar";
            sha256 = "1dyp5gviprj4p7p2pfxvqkj3xnaxlj9ppcbnvbyy2zdmdd7jb6v1";
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
            url = "https://github.com/box-project/box/releases/download/4.7.0/box.phar";
            sha256 = "0llddzrb9d428kzkhpr9psv5qmycc16aby43zsc80a1kxkm0wf9x";
          };
          rooterPharLocal = pkgs.stdenv.mkDerivation {
              name = "rooterPharBin";
              src = self;
              buildPhase = "
                ${pkgs.php84Packages.composer}/bin/composer install
                ${phpDev}/bin/php ${box} compile --composer-bin=${pkgs.php84Packages.composer}/bin/composer
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

      devShells.default = pkgs.mkShell {
        buildInputs = with pkgs; [phpDev dnsmasq pv gzip just packages.rooterDev] ++ [pkgs-stable.traefik];
      };
    });
}
