# ROOTER - commands

## Check Ports

Check the ports that have been prefilled in the .env file and adjust them to your local setup.  
If you are not sure what ports you have used in other rooter projects, you can use the following command to get an
overview.

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

## Start services

To start rooter services dnsmasq and traefik in the background run:

```bash
rooter services:start
```
> [!NOTE]  
> They will be started automatically when you start an environment.

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