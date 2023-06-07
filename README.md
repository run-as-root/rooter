# ROOTER

rooter is a local environment manager that helps orchestrating local developer environments for multiple projects.  

It brings a traefik instance that manages routing to the projects.  
Alongside that is has a lot of commands to solve day-to-day repetitive tasks.  

## Installation

```bash
./rooter install
```

in project set ``PROJECT_NAME`` env variable needs to be set through devenv.nix in project.  
Then run:

```bash
$ROOTER_BIN traefik:config-register
```

## Usage

### Print Help
```bash
$ROOTER_BIN --help
$ROOTER_BIN --help <command>
```

## Issues

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
