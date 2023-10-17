# ROOTER - Known Issues

## Update devenv

To get the latest version of devenv run this command.

```bash
nix-env -if https://github.com/cachix/devenv/tarball/latest
```

After that per environment you need to run
```bash
devenv update
```

## macOS Upgrade breaks nix installation

- check `/etc/zshrc` 
- it should include the following peace of code

```sh
# Nix
if [ -e '/nix/var/nix/profiles/default/etc/profile.d/nix-daemon.sh' ]; then
  . '/nix/var/nix/profiles/default/etc/profile.d/nix-daemon.sh'
fi
# End Nix
```
## RabbitMQ

RabbitMQ is not starting with an error similar to this:
```
Protocol 'inet_tcp': register/listen error: eaddrinuse
```

For some reason RabbitMQ opens a connection to a port already in use, even though they are not configured in `.env / devenv.nix`