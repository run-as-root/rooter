#!/usr/bin/env bash

function warning {
  >&2 printf "\033[33mWARNING\033[0m: $@\n" 
}

function error {
  >&2 printf "\033[31mERROR\033[0m: $@\n"
}

function fatal {
  error "$@"
  exit -1
}

set +e #otherwise the script will exit on error
isElementIn () {
  local e match="$1"
  shift
  for e; do [[ "$e" == "$match" ]] && return 0; done
  return 1
}