# Aula 05 — Backup, health check e rollback

**Código:** [05-backup-health-rollback.php](05-backup-health-rollback.php) · **Scripts:** [scripts/](../crm-produto/scripts/) · **Runbook:** [runbook.md](../crm-produto/docs/runbook.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/05-backup-health-rollback)

## A ideia

Três perguntas que não podem ser respondidas durante a emergência: *temos backup disso?*
*o sistema está de pé?* *como eu volto agora?*

## Backup sem restauração testada é crença

A única forma de saber que um backup funciona é **já ter restaurado**. O `.php` desta
aula faz o ciclo inteiro: gera o dump, restaura num banco descartável e confere a
contagem de registros contra a origem.

**RTO medido aqui: ~1s.** Esse número é real, não estimativa — e é o que você responde
quando perguntarem "quanto tempo para voltar?".

Detalhes que importam no script:

- `--single-transaction` — dump consistente sem travar as tabelas InnoDB.
- **Retenção** — sem ela o disco enche de backup.
- `MYSQL_PWD` em vez de `-p"senha"` — senha em argumento aparece no `ps` de qualquer
  usuário da máquina (o próprio `mysqldump` avisa que é inseguro).
- O script de restauração **recusa** se o destino for o banco em uso.
- Restaurar exige `CREATE DATABASE`, privilégio que o usuário da aplicação não tem — e
  não deve ter. Por isso credencial administrativa separada.

**Backup no mesmo disco da produção é cópia, não backup.** Um disco que falha leva os
dois. O `rsync` para destino remoto está no script, comentado, esperando o endereço real.

Além do banco: `storage/` (anexos) também precisa de backup — é dado gerado pelo
usuário, não está no Git e não se recria com migração.

## Health check checa dependência

Um `/health` que só devolve 200 sem verificar nada dá **falso positivo exatamente
quando você mais precisa dele**. O do projeto checa banco, disco e fila.

A fila entrar aí é deliberado: fila entupida é degradação silenciosa — o site responde
normalmente e nada está sendo processado.

Sem autenticação (monitor externo precisa alcançar), mas **sem vazar infraestrutura**:
"banco falhou" sim; host, usuário e stack trace, não. A aula testa isso procurando a
senha, o host e o nome da exceção no JSON de resposta.

## Rollback de código: um segundo

`deploy/rollback.sh` troca o symlink `current` para a release anterior e recarrega o
FPM. É o retorno do investimento de manter releases em disco.

## Rollback de banco: três cenários

| Situação | O que fazer |
|---|---|
| Migração rodou, sem dado novo em cima | rodar o `.down.sql` é seguro |
| Migração rodou, com dado novo já gravado | `down` perde dado → migração de correção **para frente** |
| Migração corrompeu dado existente | restaurar o backup pré-deploy |

Na prática, "rollback de banco" quase sempre significa **restaurar backup**. Migração
reversível é rede de segurança de desenvolvimento, não plano principal de incidente.

## Decidir durante o incidente é o pior momento

Por isso existe [docs/runbook.md](../crm-produto/docs/runbook.md): o que olhar quando o
site cai, como reverter, o que fazer com a fila parada.
