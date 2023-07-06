# ROOTER

rooter is a local environment manager that helps orchestrating local developer environments for multiple projects.  

It brings a traefik instance that manages routing to the projects.  
Alongside that is has a lot of commands to solve day-to-day repetitive tasks.  

## Installation

This guide assumes you have successfully installed:
- nix package manager https://nixos.org/download.html#nix-install-macos
- devenv https://devenv.sh/getting-started/
- direnv https://direnv.net

What exactly is being installed on macOS is documented here: https://nixos.org/manual/nix/stable/installation/installing-binary.html#macos-installation.

```bash
git clone git@gitlab.com:run_as_root/internal/rooter.git rooter
cd rooter
nix-shell
direnv allow
composer install
./rooter install
```
We suggest to either create 
- an alias for rooter in you `~/.basrc` or `~/.zshrc` or
- create a symlink in any dir that is included in your path

## Project setup

### Initialise Environment

To initialise a new enviroment for a project run
```bash
rooter env:init <environment-type>
```
This will copy the environment specific files to your current project and initialise them with default values.  
You can provide a custom project name by adding the option `--name=<my-custom-name>`.
```bash
rooter env:init magento2 --name="my-first-rooter-env"
```
Manually add `.devenv/` and `.env` to .gitignore

### Check Ports

Check the ports that have been prefilled in the .env file and adjust them to your local setup.  
If you are not sure what ports you have used in other rooter projects, you can use the following command to get an overview.
```bash
rooter env:list --ports
```

### Register Environment

Register environment to rooter, so it is visible in various commands.

```bash
rooter env:register
```

### Register Traefik

:::tip

Register the nginx of the project to traefik so traefik can route requests.  
```bash
rooter traefik:config:register
```

### Start the environment

Once you have completed the above steps you can start the environment for the first time.  

For the first run we suggest to start it in the foreground since this might take a while to fetch all dependencies.  

```bash
rooter env:start --debug
```

For all subsequent starts you can run it in the background with
```bash
rooter env:start
```

## Usage

### Print Help
```bash
rooter --help
rooter --help <command>
```

## Applications

### TablePlus

TablePlus can be started for a project with 
```bash
rooter tableplus
```
It will take the information from the ENV variables that have been set through .env or devenv.

In case you have installed TablePlus at a custom location you can use the ENV variable ```TABLEPLUS_BIN```.

In .env
```
TABLEPLUS_BIN=/Applications/Setapp/TablePlus.app/Contents/MacOS/TablePlus
```

Or Globally in .zshrc or .bashrc
```
export TABLEPLUS_BIN=/Applications/Setapp/TablePlus.app/Contents/MacOS/TablePlus
```

## Known Issues

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
