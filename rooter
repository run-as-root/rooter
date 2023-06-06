#!/bin/bash

## find directory where this script is located following symlinks if neccessary
export readonly ROOTER_DIR="$(
  cd "$(
    dirname "$(
      (readlink "${BASH_SOURCE[0]}" || echo "${BASH_SOURCE[0]}") \
        | sed -e "s#^../#$(dirname "$(dirname "${BASH_SOURCE[0]}")")/#"
    )"
  )" >/dev/null \
  && pwd
)"

export readonly ROOTER_HOME_DIR="${ROOTER_HOME_DIR:-"$HOME/.rooter"}"
export readonly ROOTER_SSL_DIR="${ROOTER_HOME_DIR}/ssl"
export readonly ROOTER_PROJECT_ROOT=$(pwd);
export readonly ROOTER_PROJECT_DIR=$ROOTER_PROJECT_ROOT/.rooter;

export ROOTER_PATHS="$ROOTER_PROJECT_DIR:$ROOTER_HOME_DIR:$ROOTER_DIR"

source "${ROOTER_DIR}/lib/utils.sh"
source "${ROOTER_DIR}/lib/commands.sh"

COMMAND_LIST=$(getCommandList)
CURRENT_COMMAND_PATH=$(getCommandPathByName $1)

case "$1" in
    "" | "-h" | "--help" | "help")
        source "${ROOTER_DIR}/commands/help.sh"
    ;;
    "--version")
        source "${ROOTER_DIR}/commands/version.sh"
    ;;
    *)
        # Execute command in current context
        source "$CURRENT_COMMAND_PATH"
    ;;
esac
