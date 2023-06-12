#!/usr/bin/env bash
# Usage: laravel:nginx-config-init
# Summary: Initialise nginx config
# Group: laravel
# ProjectTypes: laravel
# Help:
#   DEVENV_STATE_NGINX: path to devenv state directory for nginx (nginx.conf will be placed here)
#   DEVENV_CONFIG_NGINX: path to config where custom nginx-template.conf and laravel-template.conf are located
#
#   Example: "laravel:nginx-config-init"
# :Help

set -e

if [[ "${DEVENV_STATE_NGINX}" == "" ]]; then
    echo "DEVENV_STATE_NGINX is required" && exit 1
fi

nginxVarsAllowed=(
    '$DEVENV_STATE_NGINX'
    '$NGINX_DIR_SSL_CERTS'
    '$NGINX_HTTP_PORT'
    '$NGINX_HTTPS_PORT'
    '$DEVENV_PHPFPM_SOCKET'
    '$DEVENV_ROOT'
    '$NGINX_PKG_ROOT'
    '$PROJECT_NAME'
    '$PROJECT_HOST'
);

printf -v nginx_env_vars "%s:" "${nginxVarsAllowed[@]}" # create : separated string
nginx_env_vars=${nginx_env_vars%?} # remove final character (:)

# Prepare
rm $DEVENV_STATE_NGINX/nginx.conf 2> /dev/null;
mkdir -p $DEVENV_STATE_NGINX/tmp;

DEVENV_CONFIG_NGINX=${DEVENV_CONFIG_NGINX:=${ROOTER_DIR}/environments/laravel/nginx}

# Render configs
envsubst "${nginx_env_vars}" < $DEVENV_CONFIG_NGINX/nginx-template.conf > $DEVENV_STATE_NGINX/nginx.conf
envsubst "${nginx_env_vars}" < $DEVENV_CONFIG_NGINX/laravel-template.conf > $DEVENV_STATE_NGINX/laravel.conf

echo "nginx.conf placed at $DEVENV_STATE_NGINX/nginx.conf"
echo "laravel.conf placed at $DEVENV_STATE_NGINX/laravel.conf"
