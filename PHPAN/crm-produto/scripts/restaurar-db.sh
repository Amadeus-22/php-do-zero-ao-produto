#!/usr/bin/env bash
# Restauração. Backup NUNCA testado não é backup, é crença —
# rode isto contra um banco descartável e anote o tempo: esse é o seu RTO real.
#
#     ./scripts/restaurar-db.sh var/backups/crm-20260901.sql.gz crm_restauracao
set -euo pipefail

ARQUIVO="${1:?informe o arquivo .sql.gz}"
BANCO="${2:?informe o banco de destino (NUNCA o de produção)}"
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

set -a
[ -f "$RAIZ/.env" ] && . <(grep -E '^[A-Z_]+=' "$RAIZ/.env")
set +a

if [ "$BANCO" = "${DB_DATABASE:-}" ]; then
  echo "recusado: destino é o banco de produção/desenvolvimento em uso" >&2
  exit 1
fi

# Restaurar exige CREATE DATABASE — privilégio que o usuário da aplicação NÃO
# tem, e não deve ter. Use credencial administrativa só para isto.
USUARIO="${DB_RESTORE_USER:-$DB_USERNAME}"
export MYSQL_PWD="${DB_RESTORE_PASSWORD:-$DB_PASSWORD}"

INICIO=$(date +%s)
mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$USUARIO" \
  -e "CREATE DATABASE IF NOT EXISTS \`$BANCO\` CHARACTER SET utf8mb4"
gunzip -c "$ARQUIVO" | mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$USUARIO" "$BANCO"

echo "restaurado em $(( $(date +%s) - INICIO ))s — este é o seu RTO medido"
mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$USUARIO" \
  -e "SELECT COUNT(*) AS clientes_restaurados FROM \`$BANCO\`.clientes"
