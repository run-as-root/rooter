#!/bin/bash
# Usage: tableplus
# Summary: launch Tableplus MacOS App
# Help:
#  - set TABLEPLUS_BIN to custom bin e.g. TABLEPLUS_BIN=/Applications/Setapp/TablePlus.app/Contents/MacOS/TablePlus

TABLEPLUS_BIN=${TABLEPLUS_BIN:-/Applications/TablePlus.app/Contents/MacOS/TablePlus}

query="mysql://${DEVENV_DB_USER}:${DEVENV_DB_PASS}@127.0.0.1:${DEVENV_DB_PORT}/${DEVENV_DB_NAME}"

echo $query

set -x
open "$query" -a "${TABLEPLUS_BIN}"
