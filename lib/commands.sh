#!/usr/bin/env bash

function getCommandList () { 
  #ROOTER_DIRS_LIST=( $ROOTER_PROJECT_DIR $ROOTER_HOME_DIR $ROOTER_DIR )
  #for path in ${ROOTER_DIRS_LIST[@]}; do
  for path in ${ROOTER_PATHS//:/$'\n'}; do
    [[ ! -d "${path}" ]] && continue;
    [[ ! -d "${path}/commands/" ]] && continue;
    for commandFile in "${path}/commands/"*.sh; do
      echo "${commandFile}"
      #commandName=$(basename $commandFile)
      #commandName=${commandName%".sh"}
      #echo "CMD_NAME=${commandName} CMD_FILE=${commandFile}"
    done
    
    # Parse commands dir for groups
    local groupDirs=$(find ${path}/commands/ -maxdepth 1 -type d )
    for groupDir in ${groupDirs}; do
        [[ "$groupDir" == "${path}/commands/" ]] && continue;
        for commandFile in "${groupDir}"/*.sh; do
            echo "${commandFile}"
        done
    done
  done
}

function getCommandPathByName() {
    local command=$1
    local commandData=(${command//:/ }) # split by :
    local hasGroup=$( [[ ${#commandData[@]} > 1 ]] && echo 1 ); # count data
    local group
    if [[ $hasGroup == 1 ]]; then
        group=${commandData[0]}
        command=${commandData[1]}
    fi
    
    for commandFile in ${COMMAND_LIST}; do
        local commandName=$(basename $commandFile)
        local commandName=${commandName%".sh"}
        local commandGroup=$(basename $(dirname $commandFile))

        # Search for command in matching group, continue if group does not match
        [[ $hasGroup == 1 ]] && [[ $commandGroup != $group ]] && continue;
        
        # Match Command name in group
        if [[ "$commandName" == "$command" ]]; then
            echo "$commandFile"
            break
        fi
    done
}
