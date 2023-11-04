![rooter logo](docs/images/rooter-logo.jpg)

# ROOTER

rooter is a local environment manager that helps orchestrating local developer environments for multiple projects.  

It brings a traefik instance that manages routing to the projects.  
Alongside that is has a lot of commands to solve day-to-day repetitive tasks.  

## Prerequisites

This guide assumes you have successfully installed:
- nix package manager https://nixos.org/download.html#nix-install-macos
- devenv https://devenv.sh/getting-started/
- direnv & nix-direnv 
  - direnv https://direnv.net/docs/installation.html
    - you can use [HomeBrew](https://formulae.brew.sh/formula/direnv#default): ```brew install direnv``` 
    - or nix profile: ```nix profile install "nixpkgs#direnv"```
    - make sure to hook it into your shell: https://direnv.net/docs/hook.html
  - nix-direnv https://github.com/nix-community/nix-direnv#with-nix-profile

What exactly is being installed on macOS is documented here: https://nixos.org/manual/nix/stable/installation/installing-binary.html#macos-installation.

## Installation via flake

```bash
nix profile install --accept-flake-config "github:run-as-root/rooter?ref=main#rooter"
```

## Initial setup

rooter needs to place some configurations, initialise directories, ssl certs, etc.  
To start the installation run:
```bash
./rooter install
```
After that rooter is setup and ready to use for your projects.

## Project setup

### Quickstart

List of commands without explanation.
```bash
rooter env:create <environment-type>
direnv allow .
rooter start --debug # once done cancel with CTRL+C
rooter start
```

### Create Environment

To create a new enviroment for a project run
```bash
rooter env:create <environment-type>
```
This will copy the environment specific files to your current project and create them with default values.  
You can provide a custom project name by adding the option `--name=<my-custom-name>`.
```bash
rooter env:create magento2 --name="my-first-rooter-env"
```
Manually add `.devenv/` and `.env` to .gitignore

This command will also create a `.env` file in your project root or overwrite values for rooter.  
It will find available ports for the project and write them to the .env file.  
Ports will be selected from a range defined for each service type.

### Configure auto-initialisation

With `direnv` it is possible to automatically initialise the environment when you enter the project directory.  
To activate this feature, run the following command in your project directory.
```bash
direnv allow .
```
When this is activated the first time, dependencies for the CLI will be fetched, installed and configured for this project.

### Start the environment

Once you have completed the above steps you can start the environment for the first time.  

For the first run we suggest to start it in the foreground since this might take a while to fetch all dependencies.  

```bash
rooter start --debug
```

For all subsequent starts you can run it in the background with
```bash
rooter start
```

## Usage

### Print Help
```bash
rooter --help
rooter --help <command>
```

## Configuration

### Domains and Subdomains

By default, rooter will use the project name as the domain.  

The following subdomains are available by default if the project is named foobar:
```
*.foobar.rooter.test
```

Using `DEVENV_HTTP_SUBDOMAINS` you can define a list of subdomains that should be used for the project.  
Add the following line to your .env file and adjust the subdomains to your needs.
```bash
DEVENV_HTTP_SUBDOMAINS=my-project,de-project
```
This will result in the following domains being available for the project:
- my-project.rooter.test
- de-project.rooter.test

## COMMANDS

### Check Ports

Check the ports that have been prefilled in the .env file and adjust them to your local setup.  
If you are not sure what ports you have used in other rooter projects, you can use the following command to get an overview.
```bash
rooter list --ports
```
If you run this command for the very first time the list should be empty and show nothing.

### Register Traefik

Register the nginx of the project to traefik so traefik can route requests.
When using the `env:start` command, rooter will automatically register the traefik config.
```bash
rooter traefik:config:register
```

### Start services

To start rooter services dnsmasq and traefik in the background run:
```bash
rooter services:start
```
Note: They will be started automatically when you start an environment.

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

## KNOWN ISSUES

see [KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md)

## TEMPLATES

see [TEMPLATES.md](docs/TEMPLATES.md)

## DEVELOPMENT

### Installation Development

Clone the rooter repository to your local and change directory to rooter
```bash
git clone git@gitlab.com:run_as_root/internal/rooter.git rooter
cd rooter
```
Now we need to download all dependencies and prepare a executable.
```bash 
nix build ".#rooterDev" --impure
# if flakes are disabled:
nix build ".#rooterDev" --impure --extra-experimental-features nix-command --extra-experimental-features flakes
```

Install Composer dependencies required to executed rooter
```bash
composer install
```

Now that all dependencies are installed, we can continue with the rooter installation.  
It will initialise directories, configurations, process, ssl certs, etc.
NOTE: This is only necessary if it was not done with the normal installation.
```bash
rooterDev install
```

Last but not least, we suggest to make sure rooterDev version available globally.  
For that you should either create
- an alias for rooter in you `~/.basrc` or `~/.zshrc`
- or create a symlink in any dir that is included in your path

Examples
```bash
# add to your .bashrc or .zshrc
alias rooter-dev="<path-to-rooter>/rooter/result/bin/rooterDev"

# optional per environment:
export ROOTER_BIN="<path-to-rooter>/rooter/result/bin/rooterDev"
```

### direnv

For ease of use you can activate direnv for the rooter project.  
By default it will refresh the `rooterDev` package and make it available in your shell.
```bash
direnv allow .
```

### Commands while using dev version of rooter

#### Debugging

To debug rooter itself you need to use the '.#rooterDev' build out (`result/bin/rooterDev`).
```bash
nix build ".#rooterDev" --impure

# if flakes are disabled:
nix build ".#rooterDev" --impure --extra-experimental-features nix-command --extra-experimental-features flakes
```
