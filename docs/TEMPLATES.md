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

## Switch all packages to stable

1. edit devenv.yaml by adding the following block to the end of the file:
    ```yaml
    nixpkgs-unstable:
      url: github:NixOS/nixpkgs/nixpkgs-unstable
    ```
2. change default nixpkgs entry to:
```yaml
nixpkgs:
  url: github:NixOS/nixpkgs/nixos-23.05
```

3. edit devenv.nix by adding the following line to `let` section
```nix
let
    pkgs-unstable = import inputs.nixpkgs-unstable { system = pkgs.stdenv.system; };
in {
    #…
}
```
replace `pkgs.mailpit` with `pkgs-unstable.mailpit` in the whole file
(mailpit is not yet available in the stable channel of 23.05).  

> [!NOTE]  
> You might need to cleanup the state of some of the packages e.g. redis : `.devenv/state/redis` 
> With switching to stable packages sources, some packages can be downgraded to an older version,
> which is not compatible with state stored in `.devenv/state/`.

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

## Blackfire

Add Blackfire to the list of php extensions to install:
```nix
    languages.php = {
        enable = true;
        package = inputs.phps.packages.${builtins.currentSystem}.php81.buildEnv {
            extensions = { all, enabled }: with all; enabled ++ [ redis xdebug xsl blackfire ];
        };
    };    
```
Enable blackfire service:
```nix
    services.blackfire = {
        enable = true;
        client-id = "<insert-your-client-id>";
        client-token = "<insert-your-client-token>";
        server-id = "<insert-your-server-id>";
        server-token = "<insert-your-server-token>";
    };
```
Finally, stop the rooter environment, make sure the environment re-initialises, start the rooter environment again.