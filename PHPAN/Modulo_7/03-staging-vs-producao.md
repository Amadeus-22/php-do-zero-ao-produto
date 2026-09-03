# Aula 03 — Staging vs produção

**Código:** [03-staging-vs-producao.php](03-staging-vs-producao.php) · **Docs:** [staging.md](../crm-produto/docs/staging.md), [checklist-deploy.md](../crm-produto/docs/checklist-deploy.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/03-staging-vs-producao)

## A ideia

Staging é cópia de produção usada para validar antes que o cliente veja. O que faz
valer a pena é **paridade**: quanto mais parecido com produção, mais confiável o teste.

Staging com PHP 8.1 contra produção 8.3, ou SQLite contra MySQL, não é staging — é
outro ambiente de dev, e o bug que só aparece em produção fica garantido.

| Muda | Não muda |
|---|---|
| valores do `.env` | código (mesmo commit/tag) |
| volume e origem do dado | versão de PHP e extensões |
| quem acessa (Basic Auth, IP allowlist) | estrutura de infra |

## `APP_ENV` decide o que o usuário vê quando quebra

Produção: erro vai para o log, tela genérica. Staging: stack trace completo, para
debugar rápido. Isso é decidido no boot (`public/index.php`), não em cada controller.

Stack trace na tela entrega caminho de arquivo, versão de biblioteca e às vezes a
string de conexão.

## Banco separado é deal-breaker

Staging apontando para o banco de produção transforma qualquer teste em mutação real.
Não existe configuração que torne isso aceitável.

Se staging precisar de dado realista, use dump **anonimizado** — trocar e-mail,
telefone e documento por dado fake, mantendo formato e volume. Dado real de cliente em
staging é risco de LGPD e vazamento acidental.

## Checklist escrito

[docs/checklist-deploy.md](../crm-produto/docs/checklist-deploy.md) cobre backup antes
da migração, conferência de `migrate status` nos dois ambientes, plano de rollback
identificado e smoke test.

Se não está escrito, não é repetível: deploy às 23h, cansado, pula passo.

## Promover tag, não "a main de ontem"

`git describe` em produção tem que bater com o que foi testado em staging — senão o
teste em staging não diz nada sobre o que está no ar.

## Estado deste projeto

**Ambiente único** (local, Docker). Staging e produção ainda não existem. Está declarado
em `docs/staging.md`, com a paridade a garantir quando existirem — em vez de fingir que
já foi feito.
