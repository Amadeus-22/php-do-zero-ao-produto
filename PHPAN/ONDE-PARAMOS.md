# Onde paramos — PHPAN

**Última sessão:** 03/09/2026 · **Estado: curso completo, exercícios fechados.**

> **Síntese dos dois cursos:** [../SINTESE-PHPIAN-PHPAN.md](../SINTESE-PHPIAN-PHPAN.md)

---

## Retomar em 3 comandos

```bash
docker start crm-mysql                    # banco (os dados ficam salvos)
cd ~/Documentos/php-do-zero-ao-produto/PHPAN/crm-produto
php -S localhost:8080 -t public           # painel + API
php bin/worker.php                        # fila, em outro terminal
```

Acesse **http://localhost:8080** · `admin@exemplo.com` / `senha-de-estudo`
(também `vendedor@exemplo.com` e `leitura@exemplo.com`, para ver os papéis mudando
o que cada um consegue fazer).

**Conferir se está tudo certo:**

```bash
composer quality                          # estilo + PHPStan + 122 testes + audit
php ../Modulo_5/03-papeis-permissoes.php  # qualquer aula roda sozinha
```

---

## Como o material está organizado

Cada uma das **47 aulas** tem dois arquivos em `Modulo_N/`:

| Arquivo | O que é |
|---|---|
| `NN-slug.php` | **código que roda** — `php Modulo_5/02-access-refresh-tokens.php` |
| `NN-slug.md` | **a ideia e o porquê** das escolhas, sem repetir o código |

O metadado das 47 aulas — título, URL, status, comando, o que cada uma demonstra — está
em **[aulas.json](aulas.json)** (formato em [aulas.schema.json](aulas.schema.json)).
Antes ele era um docblock repetido no topo de cada `.php`; saiu de lá em 01/09/2026.

O código de verdade vive em **`crm-produto/`** — o projeto que atravessa o curso.
`PHPIAN/` continua **congelado**, como registro do que foi entregue lá.

Regra que combinamos: cada linguagem no seu arquivo — PHP em `.php`, SQL em `.sql`,
HTML em `views/`, JS em `.js`. Detalhes em
[crm-produto/docs/separacao-de-arquivos.md](crm-produto/docs/separacao-de-arquivos.md).

---

## O que está pronto

| Módulo | Entregue |
|---|---|
| 1 — Mapa | camadas, briefing, ambiente com PHPStan e PHPUnit |
| 2 — OOP | domínio tipado: `Cliente`, `Contato`, `Atividade`, exceções, PSR-4 |
| 3 — MVC | `Router`, `Request`/`Response`, views com layout, validação, 4 middlewares |
| 4 — API | `/api/v1` com envelope `{data}`/`{error}`, paginação, filtro, doc |
| 5 — Auth | sessão no painel, token opaco com rotação, `Gate` de papéis, reset, rate limit, auditoria |
| 6 — Produto | upload seguro, fila com worker, logs JSON Lines, lembretes em UTC, CSV, soft delete |
| 7 — Produção | `.env`, migrações em `.sql`, backup **restaurado**, `/health`, deploy versionado |
| 8 — Final | planos e limites, webhook com HMAC e idempotência, hardening, rubrica |

**Portão de qualidade:** estilo 0 · PHPStan level 5 sem erro · **137 testes, 320
asserções** · `composer audit` limpo. As 47 aulas rodam sem falha.

**Entregas fechadas em 03/09:** limite de plano aplicado de verdade (era o mais grave —
existia e nunca era chamado), rotas de reset de senha, consulta de auditoria, upload e
download de anexos, tela de lembretes, exportação em PDF, e os exercícios de PDO do
Módulo 1 rodando contra o MySQL.

---

## O que ficou pendente (e por quê)

Tudo declarado em [crm-produto/docs/rubrica-final.md](crm-produto/docs/rubrica-final.md):

| Pendência | Motivo |
|---|---|
| Staging, produção e deploy real | não há VPS nem domínio — os scripts estão em `deploy/`, prontos |
| Domínio + SPF/DKIM/DMARC | idem — plano executável em `docs/plano-dominio-email.md` |
| Rate limit no webhook | endpoint público sem limite de frequência |
| `UNIQUE` vs soft delete | e-mail de cliente excluído ainda ocupa a constraint |
| Multi-tenant | `conta_id` existe em `clientes`, mas `usuarios` é conta única |

**Prioridade sugerida quando voltar:** as duas que já afetam o comportamento hoje —
rate limit no webhook e o índice único parcial para o soft delete.

---

## Decisões que valem lembrar

**Docker em vez de SQLite.** `sudo` pede senha e eu não consigo instalar pacote; Docker
roda sem sudo. O container `crm-mysql` (MySQL 8.4, porta **3307** para não conflitar com
o MySQL da sua máquina) é o banco do projeto.

**Duas divergências do material do curso**, ambas testadas e explicadas no código:

1. O runner de migração da aula usa transação — **isso quebra no MySQL**, porque DDL faz
   commit implícito e o `commit()` estoura com a tabela já criada.
2. O repositório em memória ficou em `Infrastructure/`, não em `Domain/`, para não
   contradizer a regra "o domínio não conhece implementação".

**Bugs que só apareceram porque o código roda de verdade:** telefone descartado em
silêncio por dois repositórios, placeholder `:q` repetido (quebra com
`EMULATE_PREPARES=false`), `*/5` de cron fechando um bloco de comentário, e o
`LembreteService` conversando com PDO dentro de `Application/`.

---

## Próximo passo natural

O **PHPPRO**. O roadmap já está escrito em
[Modulo_8/06-roadmap-phppro.md](Modulo_8/06-roadmap-phppro.md), com as lacunas
classificadas em dor de hoje (S) e dor futura (F), e o sinal mensurável de cada
adiamento.
