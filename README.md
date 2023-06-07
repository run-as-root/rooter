# ROOTER

rooter is a local environment manager that helps orchestrating local developer environments for multiple projects.  

It brings a traefik instance that manages routing to the projects.  
Alongside that is has a lot of commands to solve day-to-day repetitive tasks.  

## Installation

```bash
./rooter install
./rooter traefik:config-init
```

in project set ``PROJECT_NAME`` env variable needs to be set through devenv.nix in project.  
Usually the project comes already with a .env.sample file which you can or already have copied.  

```bash
TABLEPLUS_BIN=/Applications/Setapp/TablePlus.app/Contents/MacOS/TablePlus # if it is not in /Applications
ROOTER_BIN=<path-to-rooter-executable>

NGINX_DIR_SSL_CERTS=$HOME/.rooter/ssl/certs
NGINX_HTTP_PORT=8081
NGINX_HTTPS_PORT=8443
DEVENV_NGINX_HTTP_PORT=8081
DEVENV_NGINX_HTTPS_PORT=8443
DEVENV_DB_PORT=33306
DEVENV_MAILHOG_SMTP_PORT=1025
DEVENV_MAILHOG_UI_PORT=8025
DEVENV_REDIS_PORT=63790
DEVENV_AMQP_PORT=5672
DEVENV_AMQP_MANAGEMENT_PORT=15672
DEVENV_ELASTICSEARCH_PORT=9200
```

Then register the nginx of the project to traefik so traefik can route requests

```bash
$ROOTER_BIN traefik:config-register
```

Update ``/etc/hosts`` with the paths to the frontend, mailhog, rabbitmq, what ever it supports

Example:
``` 
127.0.0.1 exocad-shop.rooter.test exocad-shop-mailhog.rooter.test exocad-shop-rabbitmq.rooter.test
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
