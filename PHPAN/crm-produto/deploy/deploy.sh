#!/usr/bin/env bash
# Deploy com releases + symlink. O padrão existe para o ROLLBACK ser instantâneo:
# trocar o link de volta é atômico e leva menos de um segundo.
set -euo pipefail

BASE="${DEPLOY_BASE:-/var/www/crm-produto}"
REPO="${DEPLOY_REPO:?defina DEPLOY_REPO}"
RELEASE="$(date +%Y%m%d-%H%M%S)"
DIR="$BASE/releases/$RELEASE"

mkdir -p "$BASE/releases" "$BASE/shared"

git clone --depth 1 "$REPO" "$DIR"
cd "$DIR"

# --no-dev: PHPUnit, PHPStan e CS-Fixer não têm o que fazer em produção.
# Menos dependência instalada = menos superfície de ataque e deploy mais rápido.
composer install --no-dev --optimize-autoloader --no-interaction

# .env e storage são COMPARTILHADOS entre releases: não vão no Git e não podem
# ser recriados a cada deploy (storage tem os anexos dos usuários).
ln -sfn "$BASE/shared/.env" "$DIR/.env"
ln -sfn "$BASE/shared/storage" "$DIR/storage"
ln -sfn "$BASE/shared/var" "$DIR/var"

php bin/migrate.php up

ln -sfn "$DIR" "$BASE/current"

# Sem recarregar o FPM, o OPcache continua servindo o código ANTIGO por horas.
sudo systemctl reload php8.3-fpm

# Smoke test: se o /health não responder 200, o deploy não terminou bem.
curl -fsS "${APP_URL:-http://localhost}/health" > /dev/null && echo "health OK" || {
  echo "health FALHOU — considere rollback" >&2
  exit 1
}

# mantém as 5 últimas releases para rollback
cd "$BASE/releases" && ls -t | tail -n +6 | xargs -r rm -rf

echo "deploy $RELEASE concluído"
