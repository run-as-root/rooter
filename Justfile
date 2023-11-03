name := "titanium"

set positional-arguments

default:
  @just --list --unsorted

# build rooter
build:
  nix build ".#rooter"

# build rooter dev version (for debugging)
build-dev:
  nix build ".#rooterDev" --impure

# build rooter phar version
build-dev-phar:
  nix build ".#rooterDevPhar"

# update flake
update:
  nix flake update

# format the nix files in this repo
fmt:
  nix fmt

# clean result directory
clean:
  rm -rf result