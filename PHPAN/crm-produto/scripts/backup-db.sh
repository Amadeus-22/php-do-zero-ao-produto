#!/usr/bin/env bash
# Backup do banco. Cron sugerido (fora do pico):
#     0 3 * * * /caminho/scripts/backup-db.sh >> /var/log/crm-backup.log 2>&1
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINO="${BACKUP_DIR:-$RAIZ/var/backups}"
RETENCAO="${BACKUP_RETENCAO:-14}"

# lê o .env sem exportar comentário nem linha vazia
set -a
[ -f "$RAIZ/.env" ] && . <(grep -E '^[A-Z_]+=' "$RAIZ/.env")
set +a

mkdir -p "$DESTINO"
DATA="$(date +%Y%m%d-%H%M%S)"
ARQUIVO="$DESTINO/crm-$DATA.sql.gz"

# MYSQL_PWD em vez de -p"senha": senha em argumento aparece no `ps` de qualquer
# usuário da máquina — e o próprio mysqldump avisa que é inseguro.
export MYSQL_PWD="${DB_PASSWORD}"

# --single-transaction: dump consistente sem travar as tabelas InnoDB
mysqldump \
  --single-transaction \
  --quick \
  --no-tablespaces \
  -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" \
  -u "${DB_USERNAME}" "${DB_DATABASE}" \
  | gzip > "$ARQUIVO"

echo "backup: $ARQUIVO ($(du -h "$ARQUIVO" | cut -f1))"

# retenção
ls -t "$DESTINO"/crm-*.sql.gz 2>/dev/null | tail -n "+$((RETENCAO + 1))" | xargs -r rm --

# REGRA: backup no mesmo disco da produção é cópia, não backup.
# Descomente com o destino remoto real:
# rsync -az "$ARQUIVO" backup-remoto:/backups/crm-produto/
