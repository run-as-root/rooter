#!/usr/bin/env bash
# Usage: redis-cli [flags] [args]
# Summary: Run redis-cli inside the redis container
# Help:
# Examples: 
# redis-cli KEYS *
# redis-cli INFO
# redis-cli --version

redis-cli -p ${DEVENV_REDIS_PORT} -h 127.0.0.1 ${@:2}
