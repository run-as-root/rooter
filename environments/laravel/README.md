# ROOTER Laravel Template

- Copy the files below to your Laravel root setup
  - `.envrc.dist`
  - `.env`
  - `devenv.nix`
  - `devenv.yaml`
- Rename the `.envrc.dist` file to `.envrc` and customize the `.env` file to your local needs
- If Laravel is already installed
  - Make sure `.devenv/state/mysql` is cleaned of any db
- Configure hosts entry
- Run `$ROOTER_BIN traefik:config-register`
- Add the follwing entries to the .gitignore file:
  ```
  .devenv.flake.nix
  .devenv
  ```
