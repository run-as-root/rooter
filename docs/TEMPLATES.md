# ROOTER - templates

## OpenSearch Magento > 2.4.6 required (Elastic v8 = OpenSearch v2)

```nix
    services.opensearch = {
        enable = true;
        settings = {
            "http.port" = lib.strings.toInt ( config.env.DEVENV_OPENSEARCH_PORT);
            "transport.port" = lib.strings.toInt ( config.env.DEVENV_OPENSEARCH_TCP_PORT );
        };
    };
```

## Pick packages from stable

Sometimes you need a package in a version which is not present in the current packages list but in an older version of
it.  
To get it we need to add the package source to the inputs in `devenv.yaml`, import and use it in `devenv.nix`

Start by adding the following code snippet to `devenv.yaml` inputs section

```yaml
  nixpkgs-stable:
    url: github:NixOS/nixpkgs/nixos-23.05
```

modifiy your `devenv.nix` file to include the import statement and make use of the package as shown for mysql.

```nix
let
    pkgs-stable = import inputs.nixpkgs-stable { system = pkgs.stdenv.system; };
in {
    # …
    services.mysql = {
        package = pkgs-stable.mariadb_104;
    };
}
```

This approach can be applied for other packages and package sources as well.

## Composer required in a specific version

modifiy your `devenv.nix` file to include the fetch for the composer phar in the version required and
create a shell script that takes precedence over the composer package in the nix store.

```nix
let
    # …
    composerPhar = builtins.fetchurl{
        url = "https://github.com/composer/composer/releases/download/2.2.22/composer.phar";
        sha256 = "1lmibmdlk2rsrf4zr7xk4yi5rhlmmi8f2g8h2izb8x4sik600dbx";
    };
    # …
in {
    # …
    scripts.composer.exec = ''
        php ${composerPhar} $@
    '';
    # …
}
```