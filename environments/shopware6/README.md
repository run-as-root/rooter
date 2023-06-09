# ROOTER Shopware6 Template

- copy the following files to your shopware root setup
  - .envrc
  - .env
  - devenv.nix
  - devenv.yaml
- customize the .env file to your local needs
- if shopware is already installed
  - make sure ```.devenv/state/mysql``` is cleaned of any db 
  - remove install.lock
  - run ``bin/console system:install --basic-setup``
- configure hosts entry
- run ``$ROOTER_BIN traefik:config-register``
