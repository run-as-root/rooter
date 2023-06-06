#!/bin/bash
# Usage: querious
# Summary: launch Querious app 

query="mysql://${DEVENV_DB_USER}:${DEVENV_DB_PASS}@127.0.0.1:${DEVENV_DB_PORT}/${DEVENV_DB_NAME}"

open "querious://connect/new?host=127.0.0.1&user=${DEVENV_DB_USER}&password=${DEVENV_DB_PASS}&use-compression=false&database=${DEVENV_DB_NAME}&port=${DEVENV_DB_PORT}"

