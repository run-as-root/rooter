#!/bin/bash
# Usage: traefik-config-register.sh
# Summary: Register a project specific traefik config
# Group: traefik
# Help:
# uses "${ROOTER_DIR}/etc/traefik/conf.d/endpoint-tmpl.yml" as template
# to render a config to "${ROOTER_HOME_DIR}/traefik/conf.d/${PROJECT_NAME}.yml"
# :Help

set -e

envVars="$(printf '${%s} ' $(env | cut -d'=' -f1))" # determine all env vars
sourceFile="${ROOTER_DIR}/etc/traefik/conf.d/endpoint-tmpl.yml"
targetFile="${ROOTER_HOME_DIR}/traefik/conf.d/${PROJECT_NAME}.yml"

envsubst "${envVars}" < "$sourceFile" > "$targetFile"

echo "
Registered traefik configuration for $PROJECT_NAME

$(cat $targetFile)
"
