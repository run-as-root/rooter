#!/bin/bash
# Usage: traefik-start
# Summary: Start Traefik in foreground
# Group: traefik

traefik --configfile=${ROOTER_HOME_DIR}/traefik/traefik.yml
