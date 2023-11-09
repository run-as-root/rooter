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
        - you can use [HomeBrew](https://formulae.brew.sh/formula/direnv#default): `brew install direnv`
        - or nix profile: `nix profile install "nixpkgs#direnv"`
        - make sure to hook it into your shell: https://direnv.net/docs/hook.html
    - nix-direnv https://github.com/nix-community/nix-direnv#with-nix-profile

What exactly is being installed on macOS is documented
here: https://nixos.org/manual/nix/stable/installation/installing-binary.html#macos-installation.

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

When this is activated the first time, dependencies for the CLI will be fetched, installed and configured for this
project.

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

```bash
rooter --help
rooter --help <command>
```

## Configuration

rooter can be configured via environment variables.  
To find out more about the available options and how to use them,
please refer to the [configuration options](docs/CONFIGURATION.md).

## COMMANDS

To find out more about the available commands and how to use them, please refer to the [commands](docs/COMMANDS.md).

## FURTHER DOCUMENTATION

- [TEMPLATES](docs/TEMPLATES.md)
- [KNOWN ISSUES](docs/KNOWN_ISSUES.md)
- [DEVELOPMENT & CONTRIBUTION](docs/DEVELOPMENT.md)