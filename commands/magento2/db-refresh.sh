#!/usr/bin/env bash
# Usage: magento2:db-refresh [options] [arguments]
# Summary: Refresh database from dump
# Group: magento2
# Help:
#  Example: "magento2:db-refresh"
#  Example: "magento2:db-refresh /tmp/mydumpfile.sql"
#  More
# :Help

set -xe

mysqlDumpFile="${ROOTER_PROJECT_DIR}/.rooter/db/${DEVENV_DB_NAME}.sql"

mysqlParams="-u${DEVENV_DB_USER} -p${DEVENV_DB_PASS} --host=localhost --port=${DEVENV_DB_PORT}"

mysql ${mysqlParams} -e "DROP DATABASE IF EXISTS ${DEVENV_DB_NAME}; CREATE DATABASE IF NOT EXISTS ${DEVENV_DB_NAME};"

mysql $mysqlParams --database=${DEVENV_DB_NAME} < "${ROOTER_PROJECT_DIR}/.rooter/db/${mysqlDumpFile}.sql" #

#cp .ddev/magento/app/etc/env.php app/etc/env.php

bin/magento config:data:import config/store dev/rooter

bin/magento setup:upgrade

magerun2 admin:user:delete -f -n admin || true
magerun2 admin:user:create --admin-user=admin --admin-password=admin123 --admin-email=admin@run-as-root.sh --admin-firstname=Admin --admin-lastname=Admin

# Reindex so elasticsearch gets the updated data
bin/magento indexer:reindex
