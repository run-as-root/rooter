# ROOTER - commands

## Check Ports

To get an overview of ports you have used in other rooter projects, use the following command.

```bash
rooter list --ports
```

If you run this command for the very first time the list should be empty and show nothing.

## Register Traefik

Register the nginx of the project to traefik so traefik can route requests.
When using the `env:start` command, rooter will automatically register the traefik config.

```bash
rooter traefik:config:register
```

## Start & Stop services

To start rooter services dnsmasq and traefik in the background run:

```bash
rooter services:start
```
> [!NOTE]  
> They will be started automatically when you start an environment.

To stop the rooter services run:

```bash
rooter services:stop
```

## TablePlus

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

## Magento 2

### Magento 2 Installation

The `magento2:db-install` command is designed to initialize a fresh database for a Magento 2 project.  
It will dropped and recreate the database, run `setup:install`, `setup:upgrade`, 
optionally import config data from files and run a full reindex.  
It will get all required data from env variables.

> [!NOTE] 
> Make sure to backup important data before running this command

#### Usage

```bash
rooter magento2:db-install [options]
```
**Options**

- `--config-data-import`: Import config data after installation.
- `--skip-reindex`: Skip reindexing after importing the dump.

#### Examples

**Basic Usage:**

```bash
rooter magento2:db-install
```

This will initialize a fresh database, run `setup:install` and `setup:upgrade`, and perform necessary configurations.

**Import Config Data:**

```bash
rooter magento2:db-install --config-data-import --skip-reindex
```

Additionally, imports config data from files with `config:data:import` after the installation and 
skips the reindexing step after importing the dump.


### Magento 2 Refresh Database

The `magento2:db-refresh` command allows you to refresh the Magento 2 database from a dump file.  
It drops and recreates the database, imports the specified dump, runs `setup:upgrade`, and performs additional configurations.

> [!NOTE]
> Make sure to backup important data before running this command

Magerun2 is used to delete and create the admin user.  
Use env variable `MAGERUN2_BIN` to set a custom path Magerun2 binary (default: `magerun2`).

#### Usage

```bash
rooter magento2:db-refresh <dump-file> [options]
```
- `<dump-file>`: path to the database dump file (required).

**Options**

- `--config-data-import`: Import config data after installation.
- `--skip-reindex`: Skip reindexing after importing the dump.

#### Examples

**Basic Usage:**

```bash
rooter magento2:db-refresh /path/to/your/db-dump.sql
```

This will refresh the database from the specified dump file.

**Import Config Data:**

```bash
rooter magento2:db-refresh /path/to/your/db-dump.sql --config-data-import --skip-reindex
```

Additionally, imports config data from files with `config:data:import` after the installation and
skips the reindexing step after importing the dump.
