#!/usr/bin/env bash
# Alerta pobre e funcional: conta erros recentes no log e avisa se passar do limite.
# Cron: */15 * * * * /caminho/scripts/checar-taxa-erro.sh
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG="${LOG_FILE:-$RAIZ/var/logs/app.jsonl}"
LIMITE="${LIMITE_ERROS:-10}"
JANELA_MIN="${JANELA_MIN:-15}"

[ -f "$LOG" ] || { echo "sem log ainda"; exit 0; }

CORTE=$(date -u -d "-${JANELA_MIN} minutes" +%Y-%m-%dT%H:%M:%S)

# JSON Lines permite filtrar por nível e por timestamp com grep/awk simples
ERROS=$(awk -v corte="$CORTE" '
  /"nivel":"(error|critical)"/ {
    if (match($0, /"timestamp":"[^"]+"/)) {
      ts = substr($0, RSTART+13, 19)
      if (ts >= corte) n++
    }
  }
  END { print n+0 }
' "$LOG")

echo "erros nos últimos ${JANELA_MIN} min: $ERROS (limite $LIMITE)"

if [ "$ERROS" -gt "$LIMITE" ]; then
  echo "ALERTA: taxa de erro alta ($ERROS em ${JANELA_MIN} min)" >&2
  # aqui entraria o envio real (mail, webhook do Slack, etc.)
  exit 1
fi
