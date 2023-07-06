# ROOTER - Known Issues

### macOS Upgrade breaks nix installation

- check `/etc/zshrc` 
- it should include the following peace of code

```sh
# Nix
if [ -e '/nix/var/nix/profiles/default/etc/profile.d/nix-daemon.sh' ]; then
  . '/nix/var/nix/profiles/default/etc/profile.d/nix-daemon.sh'
fi
# End Nix
```

## devenv.sh templates

### OpenSearch Magento > 2.4.6 required (Elastic v8 = OpenSearch v2)

```nix
    services.opensearch = {
        enable = true;
        settings = {
            "http.port" = elasticsearchPort;
            "transport.port" = 9300;
        };
    };
```
