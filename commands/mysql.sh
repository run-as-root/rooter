#!/usr/bin/env bash
# Usage: mysql <subcommand>
# Summary: execute mysql commands
# Commands:
#  connect   Launches an interactive mysql session within the current project environment
#  import    Reads data from stdin and loads it into the current projects mysql database
#  dump      Dumps database via mysqldump to pwd or redirect to provided file
# :Commands
# Help:
#  mysql                         Launch cli
#  mysql connect -A              Launch cli with -A param
#  mysql import < /tmp/dump.sql  Import dump from /tmp/dump.sql
#  mysql dump                    Will dump to pwd
#  mysql dump > /tmp/dump.sql    Will dump to /tmp/dump.sql
# :Help

mysqlParams="-u${DEVENV_DB_USER} -p${DEVENV_DB_PASS} --host=localhost --port=${DEVENV_DB_PORT}"

## sub-command execution
subcommand=$2
case "$subcommand" in
    "" | connect)
        set -x
        mysql $mysqlParams --database=${DEVENV_DB_NAME} "${@:3}"
        ;;
    import)
        set -x
        mysql $mysqlParams --database=${DEVENV_DB_NAME}
        ;;
    dump)
        timestamp=$(date +%s)
        MYSQL_DUMP_FILE=${3:-"dump-$(date +%s).sql"}
        mysqldump $mysqlParams ${DEVENV_DB_NAME} > $MYSQL_DUMP_FILE
        echo "dumped to $MYSQL_DUMP_FILE"
        ;;
    *)
        fatal "The command \"$subcommand\" does not exist. Please use --help for usage."
        ;;
esac
