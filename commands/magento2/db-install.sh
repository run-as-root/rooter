#!/bin/bash
# Usage: magento2:db-install [options] [arguments]
# Summary: Initialise fresh database
# Group: magento2
# ProjectTypes: magento2
# Help:
#   Example: "magento2:db-install"
# :Help

set -xe

mysqlParams="-u${DEVENV_DB_USER} -p${DEVENV_DB_PASS} --host=localhost --port=${DEVENV_DB_PORT}"

mysql ${mysqlParams} -e "DROP DATABASE IF EXISTS ${DEVENV_DB_NAME}; CREATE DATABASE IF NOT EXISTS ${DEVENV_DB_NAME};"

bin/magento setup:install \
    --db-host=localhost:${DEVENV_DB_PORT} --db-name=${DB_NAME} --db-user=${DEVENV_DB_USER} --db-password=${DEVENV_DB_PASS} \
    --admin-email=admin@mwltr.de --admin-firstname=Admin --admin-lastname=Admin --admin-password=admin123 --admin-user=admin \
    --backend-frontname=admin  \
    --base-url=http://${PROJECT_HOST}/ \
    --currency=EUR --language=en_US --timezone=Europe/Berlin --ansi \
    --session-save=redis \
    --session-save-redis-host=127.0.0.1 \
    --session-save-redis-port=${DEVENV_REDIS_PORT} \
    --session-save-redis-timeout=2.5 \
    --session-save-redis-db=2 \
    --cache-backend=redis \
    --cache-backend-redis-server=127.0.0.1 \
    --cache-backend-redis-db=0 \
    --cache-backend-redis-port=${DEVENV_REDIS_PORT}  \
    --page-cache=redis \
    --page-cache-redis-server=127.0.0.1 \
    --page-cache-redis-db=1 \
    --page-cache-redis-port=${DEVENV_REDIS_PORT}  \
    --es-hosts="127.0.0.1:${DEVENV_ELASTICSEARCH_PORT}" --es-enable-ssl=0 --es-user="" --es-pass="" \
    --amqp-host="127.0.0.1" --amqp-port="${DEVENV_AMQP_PORT}" --amqp-user="${DEVENV_AMQP_USER}" --amqp-password="${DEVENV_AMQP_PASS}" --amqp-virtualhost="/"

bin/magento setup:upgrade

bin/magento config:data:import config/store dev/rooter

# Reindex so elasticsearch is gets the updated data
bin/magento indexer:reindex
