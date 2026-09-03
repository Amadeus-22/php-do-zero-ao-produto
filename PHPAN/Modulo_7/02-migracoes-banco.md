# Aula 02 — Migrações de banco

**Código:** [02-migracoes-banco.php](02-migracoes-banco.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/02-migracoes-banco)

## A ideia

Migração é mudança de schema com três propriedades:

1. **Versionada** — ordem e identidade (prefixo `YYYYMMDD_NNNN`, que evita colisão
   quando duas pessoas criam migração no mesmo dia).
2. **Rastreada** — a tabela `migrations` sabe o que já rodou, então rodar de novo é
   seguro.
3. **Reversível** — toda `up` tem uma `down`, mesmo que você raramente reverta.

Sem isso, "qual é o schema de produção?" vira pergunta sem resposta confiável: cada
ambiente diverge um pouco até uma query quebrar só em um deles.

## SQL em `.sql`, runner em `.php`

O schema mora em `migrations/*.up.sql` e `*.down.sql`. O runner continua PHP porque o
que ele faz **é lógica**: lê o que já rodou, ordena, executa e trata erro.

Heredoc com `CREATE TABLE` dentro de PHP esconde o SQL do editor, do `diff` e de
qualquer ferramenta de banco.

## A divergência do material — e por que ela importa

A aula envolve cada migração numa transação. **Isso não funciona no MySQL.**

DDL (`CREATE`, `ALTER`, `DROP`) provoca **commit implícito**: a transação morre no meio,
e o `commit()` seguinte estoura `"There is no active transaction"` — com a tabela **já
criada**. Foi exatamente o que aconteceu ao rodar o runner da aula aqui.

O `.php` prova isso: abre uma transação, faz um `CREATE TABLE`, e mostra que
`inTransaction()` já é `false`.

**Consequência prática:** no MySQL, uma migração com duas alterações que falha na
segunda deixa a primeira aplicada. Daí duas regras:

- **uma alteração estrutural por migração**;
- **backup antes de migração destrutiva** (Módulo 7, aula 5) não é opcional.

Em PostgreSQL o runner da aula funcionaria — DDL é transacional lá.

## Migração de schema não carrega dado de negócio

`INSERT` de cliente de teste dentro de migração é seed, e seed é outro script
(`bin/seed-usuarios.php`). A aula verifica que nenhuma migração faz `INSERT`.

## A regra que não se quebra

Migração já aplicada em algum ambiente é **histórico**. Mudar o conteúdo sem mudar o
nome faz cada ambiente ter um schema diferente do que o arquivo diz ter. Achou erro?
Crie uma migração nova de ajuste.
