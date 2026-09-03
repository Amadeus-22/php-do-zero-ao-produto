#!/usr/bin/env bash
# Rollback de CÓDIGO. Rollback de BANCO é outra conversa: ver docs/runbook.md.
set -euo pipefail

BASE="${DEPLOY_BASE:-/var/www/crm-produto}"
ANTERIOR="$(ls -t "$BASE/releases" | sed -n '2p')"

[ -n "$ANTERIOR" ] || { echo "não há release anterior" >&2; exit 1; }

INICIO=$(date +%s)
ln -sfn "$BASE/releases/$ANTERIOR" "$BASE/current"
sudo systemctl reload php8.3-fpm

echo "rollback para $ANTERIOR em $(( $(date +%s) - INICIO ))s"
