#!/usr/bin/env bash
if [[ "${PROJECT_NAME}" == "" ]]; then
    echo "This command should be executed in a project context. PROJECT_NAME is empty" && exit 1
fi

echo "rooter $(cat ${ROOTER_DIR}/VERSION)
ROOTER_DIR: ${ROOTER_DIR}
ROOTER_PATHS:
$(
    for path in ${ROOTER_PATHS//:/$'\n'}; do
        echo "  - ${path}"
    done
)
"
