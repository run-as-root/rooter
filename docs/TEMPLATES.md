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

## Custom nginx config

rooter supports a per environment nginx config, different from the default one from the environment templates.  
To use it, copy the nginx directory from the [environment templates](/environments) to `PROJECT_ROOT/.rooter/nginx` or
any other directory you prefer.  
Make sure you copy and keep all the files from the nxginx directory. Then customize the nginx config to your needs.  
Finally add the following to your `.env` file, to point rooter to your custom nginx config:

```dotenv
DEVENV_CONFIG_NGINX=.rooter/nginx
```

After you have done that, stop and start the environment again.

## Custom TLD

First Generate certificates for `your-domain-name.test`

```bash
rooter certs:generate your-domain-name.test
```

Copy the file [templates/nginx/tld/nginx-template.conf](`/templates/nginx/tld/nginx-template.conf`)
to `PROJECT_ROOT/.rooter/nginx/nginx-template.conf`.

Open the file and replace all occurrences of `${PROJECT_TLD}` with `your-domain-name.test`.

Finally, add the following to your `.env` file

```dotenv
PROJECT_TLD=your-domain-name.test
DEVENV_CONFIG_NGINX=.rooter/nginx
```

That's it. You can now start rooter and it will use your custom TLD.   
``bash
rooter env:start
``
Traefik config will be automatically registered. So you can open the traefik dashboard and check.  
You can also verify `PROJECT_ROOT/.devenv/state/nginx/nginx.conf`, it should now contain your TLD.  