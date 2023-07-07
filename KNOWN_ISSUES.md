# ROOTER - Known Issues

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

## php-fpm is not stopped

By default devenv uses ``honcho`` process-manager.  
For a yet unknown reason, in some scenarios, honcho can not stop php-fpm.  
Reason is that the PID tracked by honcho does not match the actual PID the process is running with.  

In that case you can use another process-manager: ``process-compose``

Add this to your `devenv.nix`
```nix
    process.implementation="process-compose";
    process.process-compose={
        "port" = "9999";
        "tui" = "false";
        "version" = "0.5";
    };
```
