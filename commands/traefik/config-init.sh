#!/bin/bash
# Usage: traefik-init
# Summary: Initialise rooter traefik configuration for user in $HOME
# Group: traefik

# @todo check if file exists and require --force as param

mkdir -p "${ROOTER_HOME_DIR}/traefik/"
mkdir -p "${ROOTER_HOME_DIR}/traefik/conf.d/"
mkdir -p "${ROOTER_HOME_DIR}/traefik/logs/"

traefikConf="${ROOTER_HOME_DIR}/traefik/traefik.yml"

rm $traefikConf 2> /dev/null; # remove existing traefik config

envVars="$(printf '${%s} ' $(env | cut -d'=' -f1))"

envsubst "${envVars}" < "${ROOTER_DIR}/etc/traefik.yml" > "${ROOTER_HOME_DIR}/traefik/traefik.yml"

cp "${ROOTER_DIR}/etc/traefik/conf.d/default.yml" "${ROOTER_HOME_DIR}/traefik/conf.d/default.yml"
