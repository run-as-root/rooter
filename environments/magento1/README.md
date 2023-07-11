# ROOTER Magento 1 Template

- Copy the files below to your Laravel root setup
  - `.envrc`
  - `.env`
  - `devenv.nix`
  - `devenv.yaml`
- If Magento 1 is already installed
  - Make sure `.devenv/state/mysql` is cleaned of any db
- Run `$ROOTER_BIN traefik:config:register`
- Add the following entries to the .gitignore file:
  ```
  .devenv.flake.nix
  .devenv
  ```
- It is assumed that the magento 1 project code lives in the `./htdocs` directory
