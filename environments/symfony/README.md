# ROOTER Symfony Template

- copy the following files to your shopware root setup
  - .envrc
  - .env
  - devenv.nix
  - devenv.yaml
- customize the .env file to your local needs
- if symfony is already installed
  - make sure `.devenv/state/mysql` is cleaned of any db
- run `rooter traefik:config:register`
