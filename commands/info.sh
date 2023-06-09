#!/usr/bin/env bash
# Usage: info
# Summary: summary about environment
# Help:
# This will print out an extensive summary about the project
# :Help

if [[ "${PROJECT_NAME}" == "" ]]; then
    echo "This command should be executed in a project context. PROJECT_NAME is empty" && exit 1
fi

echo "PROJECT: ${PROJECT_NAME}"
echo "
app:            http://${PROJECT_HOST}
mailhog:        http://${PROJECT_NAME}-mailhog.rooter.test
AMQP-admin:     http://${PROJECT_NAME}-amqp.rooter.test  u: ${DEVENV_AMQP_USER} p:${DEVENV_AMQP_PASS}

nginx:          http(${DEVENV_HTTP_PORT}) https(${DEVENV_HTTPS_PORT})
DB:             mysql://${DEVENV_DB_USER}:${DEVENV_DB_PASS}@127.0.0.1:${DEVENV_DB_PORT}/${DEVENV_DB_NAME}
redis:          http://127.0.0.1:${DEVENV_REDIS_PORT}
AMQP:           http://127.0.0.1:${DEVENV_AMQP_PORT}
AMQP-admin:     http://127.0.0.1:${DEVENV_AMQP_MANAGEMENT_PORT}
elasticsearch:  http://127.0.0.1:${DEVENV_ELASTICSEARCH_PORT}

mailhog UI:     http://127.0.0.1:${DEVENV_MAILHOG_UI_PORT}
mailhog SMTP:   http://127.0.0.1:${DEVENV_MAILHOG_SMTP_PORT}
";
