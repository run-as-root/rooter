# ROOTER - development guide

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
