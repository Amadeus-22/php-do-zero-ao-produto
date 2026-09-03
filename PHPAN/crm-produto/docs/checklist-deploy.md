# Checklist de promoção staging → produção

Preencher a cada deploy. Checklist só na cabeça não é repetível — deploy às 23h,
cansado, pula passo.

## Antes

- [ ] `git log` de staging e produção alinhados (promover **tag**, não "o que estiver na main")
- [ ] `php bin/migrate.php status` conferido nos dois ambientes
- [ ] **Backup de produção tirado** (`scripts/backup-db.sh`) — inegociável antes de migração
- [ ] Testado em staging com volume representativo, não com 10 linhas
- [ ] Plano de rollback identificado: release anterior + arquivo de backup
- [ ] Fora do horário de pico (se já há uso real)

## Durante

- [ ] `deploy/deploy.sh`
- [ ] Migrações aplicadas sem erro
- [ ] PHP-FPM recarregado (senão o OPcache serve o código antigo)

## Depois — smoke test

- [ ] `GET /health` responde 200 com `database: ok`
- [ ] Login com usuário de teste
- [ ] Criar um cliente `TESTE-DEPLOY` e confirmar na listagem
- [ ] Worker de pé: `systemctl status crm-worker`
- [ ] Um job leve processado (checar `var/logs/app.jsonl`)
- [ ] Remover o registro de teste

## Registro

| Data | Tag | Duração | Quem | Ocorrências |
|---|---|---|---|---|
| | | | | |
