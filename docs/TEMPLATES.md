# ROOTER - devenv.sh templates

## OpenSearch Magento > 2.4.6 required (Elastic v8 = OpenSearch v2)

```nix
    services.opensearch = {
        enable = true;
        settings = {
            "http.port" = lib.strings.toInt ( config.env.DEVENV_ELASTICSEARCH_PORT);
            "transport.port" = lib.strings.toInt ( config.env.DEVENV_ELASTICSEARCH_TCP_PORT );
        };
    };
```
## Pick packages from stable

Sometimes you need a package in a version which is not present in the current packages list but in an older version of it.  
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
