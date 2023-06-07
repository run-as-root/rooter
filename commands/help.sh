#!/usr/bin/env bash
# Usage: help [options] [arguments]
# Summary: Show help for rooter or a command
# Help:
#   Example: "help info"
# :Help

set -e

printCommandList() {
  local groups=()
  local commands=()
  local summaries=()
  local commandsToGroup=()
  local longest_command=0
  local command

  #for command in $(source "${ROOTER_DIR}/lib/command-list.sh"); do
  for command in $COMMAND_LIST; do
    commandName=$(basename $command)
    commandName=${commandName%".sh"}
    local file=$command
    if [ ! -h "$file" ]; then
      local summary="$(summary "$file")"
      local groupName="$(groupName "$file")"
      
      [[ ! -n "$groupName" ]] && groupName="default"
      
      if ! isElementIn "$groupName" "${groups[@]}"; then
        groups+=( $groupName )
      fi
      
      if [ -n "$summary" ]; then
        commands["${#commands[@]}"]="$commandName"
        summaries["${#summaries[@]}"]="$summary"
        commandsToGroup["${#commandsToGroup[@]}"]="$groupName"

        if [ "${#commandName}" -gt "$longest_command" ]; then
          longest_command="${#commandName}"
        fi
      fi
    fi
  done

  local index
  local columns="$(tput cols)"
  local summary_length=$(( $columns - $longest_command - 5 ))

  for groupName in ${groups[@]}; do
    [[ $groupName != 'default' ]] && printf "\033[33m $groupName\033[0m\n"
    for (( index=0; index < ${#commands[@]}; index++ )); do
      commandGroup=${commandsToGroup[$index]}

      [[ $commandGroup != $groupName ]] && continue;
      
      commandName=${commands[$index]}
      [[ "$commandGroup" != 'default' ]] && commandName="${commandGroup}:${commandName}"

      summary=$(truncate "$summary_length" "${summaries[$index]}")
      
      printf "\033[32m   %-${longest_command}s \033[0m %s\n" "${commandName}" "$summary"
    done
  done
}

printCommandHelp() {
  local file="$1"
  local usage="$(usage "$file")"

  if [ -n "$usage" ]; then
    local summary="$(summary "$file")"
    [ -n "$summary" ] && printf "\033[33mSummary:\033[0m\n  $summary\n\n"

    printf "\033[33mUsage:\033[0m\n  $usage\n\n"
    
    local projectType="$(projectType "$file")"
    [ -n "$projectType" ] && printf "\033[33mProjectType:\033[0m $projectType\n\n"

    #local groupName="$(groupName "$file")"
    #[ -n "$groupName" ] && echo "Render Group and Group Commands"

    local commands="$(helpCommandList "$file")"
    [ -n "$commands" ] && printf "\033[33mCommands:\033[0m\n$commands\n\n"

    local help="$(help "$file")"
    [ -n "$help" ] && printf "\033[33mHelp:\033[0m\n\033[32m$help\033[0m"
  else
    echo "Sorry, this command isn't documented yet."
  fi
}

printUsage() {
printf "\033[33mUsage:\033[0m
  command [options] [arguments]

\033[33mOptions:\033[0m\033[32m
   -h, --help            Display this help message
       --version         Show version
\033[0m
\033[33mAvailable commands:\033[0m
$(printCommandList)

See 'rooter help <command>' for information on a specific command."
}

summary() {
  sed -n "s/^# Summary: \(.*\)/\1/p" "$1"
}

projectType() {
  sed -n "s/^# ProjectTypes: \(.*\)/\1/p" "$1"
}

groupName() {
  sed -n "s/^# Group: \(.*\)/\1/p" "$1"
}

helpCommandList() {
  #awk '/^[^#]/{p=0} /^# Commands:/{p=1} p' "$1" | sed "s/^# Commands: //;s/^# //;s/^#//"
  sed -e '/Commands:/,/:Commands/!d' "$1" | grep -v "Commands:" | grep -v ":Commands" | sed 's/[#]//g'
}

usage() {
  sed -n "s/^# Usage: \(.*\)/\1/p" "$1"
}

help() {
  #awk '/^[^#]/{p=0} /^# Help:/{p=1} p' "$1" | sed "s/^# Help: //;s/^# //;s/^#//"
  sed -e '/Help:/,/:Help/!d' "$1" | grep -v "Help:" | grep -v ":Help" | sed 's/[#]//g'
}

truncate() {
  local max_length="$1"
  local string="$2"

  if [ "${#string}" -gt "$max_length" ]; then
    local length=$(( $max_length - 3 ))
    echo "${string:0:$length}..."
  else
    echo "$string"
  fi
}

command="$2"
case "$command" in
    "" | "-h" | "--help") 
        printUsage
    ;;

    *)
    HELP_COMMAND=$(getCommandPathByName $command)

    if [ -n "$HELP_COMMAND" ]; then
        printCommandHelp "$HELP_COMMAND"
    else
        echo "rooter: no such command \`$command'" >&2
        exit 1
    fi
esac
