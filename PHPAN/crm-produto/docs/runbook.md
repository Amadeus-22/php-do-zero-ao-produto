# Runbook — o que fazer quando quebrar

Decidir estratégia **durante** o incidente é o pior momento possível.

## O site caiu

1. `curl -s https://crm.exemplo.com/health` — o que está `fail`?
2. `database: fail` → o banco está de pé? disco cheio? conexões esgotadas?
3. `disk: low` → provável log sem rotação (`deploy/logrotate.conf`)
4. Se o problema começou logo após um deploy: **rollback primeiro, investiga depois**.

## Rollback de código

```bash
deploy/rollback.sh   # troca o symlink current e recarrega o FPM
```

Instantâneo, porque as 5 últimas releases ficam em disco.

## Rollback de banco — três cenários diferentes

| Situação | O que fazer |
|---|---|
| Migração rodou, **sem** dado novo em cima | rodar o `.down.sql` é seguro |
| Migração rodou, **com** dado novo já gravado | `down` perde dado → escreva uma migração de correção **para frente** |
| Migração corrompeu dado existente | restaurar do backup tirado antes do deploy |

Na prática, "rollback de banco" quase sempre significa **restaurar backup**. Migração
reversível é rede de segurança de desenvolvimento, não o plano principal de incidente.

> No MySQL há um agravante: DDL faz commit implícito. Uma migração com duas alterações
> que falha na segunda deixa a primeira aplicada. Daí a regra de uma alteração
> estrutural por migração.

## A fila parou

- `systemctl status crm-worker` — o processo está vivo?
- Jobs em `falhou`: `SELECT tipo, erro, COUNT(*) FROM jobs WHERE status='falhou' GROUP BY 1,2`
- Job travado em `processando` há muito tempo: worker morreu no meio. Voltar para
  `pendente` manualmente e investigar por que não teve timeout.

## Restauração de backup

```bash
DB_RESTORE_USER=admin DB_RESTORE_PASSWORD=... \
  scripts/restaurar-db.sh var/backups/crm-AAAAMMDD.sql.gz crm_restauracao
```

**Nunca restaure direto sobre produção sem antes validar num banco separado.** O script
recusa se o destino for o banco em uso.

Último teste de restauração: **01/09/2026 — 1s, 4 clientes conferidos.** Esse tempo é o
RTO real; sem tê-lo medido, qualquer número seria invenção.
