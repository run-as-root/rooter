![rooter logo](docs/images/rooter-logo.jpg)

# ROOTER

rooter is a local environment manager that helps orchestrating local developer environments for multiple projects.  

It brings a traefik instance that manages routing to the projects.  
Alongside that is has a lot of commands to solve day-to-day repetitive tasks.  

## Prerequisites

This guide assumes you have successfully installed:
- nix package manager https://nixos.org/download.html#nix-install-macos
- devenv https://devenv.sh/getting-started/
- direnv https://direnv.net/docs/installation.html
  - you can use HomeBrew: https://formulae.brew.sh/formula/direnv#default
  - make sure to hook it into your shell: https://direnv.net/docs/hook.html

What exactly is being installed on macOS is documented here: https://nixos.org/manual/nix/stable/installation/installing-binary.html#macos-installation.

## Installation

Clone the rooter repository to your local and change directory to rooter
```bash
git clone git@gitlab.com:run_as_root/internal/rooter.git rooter
cd rooter
```
No we need to download all dependencies using nix.  
This can be triggered using direnv or using nix.  
Choose one:

1. direnv
```bash
direnv allow .
```

2. nix
```bash 
nix-shell
# … wait for the process to finish, it will take quite a few minutes if executed for the first time
exit # exit the shell
```

Install Composer dependencies required to executed rooter
```bash
composer install
```

Now that all dependencies are installed, we can continue with the rooter installation.  
It will initialise directories, configurations, process, ssl certs, etc.  
```bash
./rooter install
```

Last but not least, we suggest to make sure rooter binary is globally available.  
For that you should either create 
- an alias for rooter in you `~/.basrc` or `~/.zshrc` e.g. `alias rooter=/<path-to-rooter>/rooter`
- or create a symlink in any dir that is included in your path
- or add the rooter directory to the PATH `export $PATH="$PATH:/<path-to-rooter>/rooter"`

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

This command will also create a `.env` file in your project root or overwrite values for rooter.  
It will find available ports for the project and write them to the .env file.  
Ports will selected from a range defined for each service type.

### Configure auto-initialisation

With direnv it is possible to automatically initialise the environment when you enter the project directory.  
To activate this feature, run the following command in your project directory.
```bash
direnv allow .
```
When this is activated the first time, dependencies for the CLI will be fetched, installed and configured for this project.

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
rooter env:list --ports
```
If you run this command for the very first time the list should be empty and show nothing.

### Register Environment

Register environment to rooter, so it is visible in various commands.  
When using the `env:start` command, rooter will automatically register the environment.  

```bash
rooter env:register
```

### Register Traefik

Register the nginx of the project to traefik so traefik can route requests.
When using the `env:start` command, rooter will automatically register the traefik config.
```bash
rooter traefik:config:register
```

### Start rooter

To start rooter with dnsmasq and traefik in the background run:

```bash
rooter start
```

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

For local development of rooter you can use the default installation.  
PHP Debugging can be enabled by replacing this line in ``shell.nix``:

```
use nix -o shell.dev.nix
```
