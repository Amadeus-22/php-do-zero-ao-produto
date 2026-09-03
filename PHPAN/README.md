# PHPAN — Intermediário

Curso `phpan` de `cursos.asllanmaciel.com.br` — 8 módulos, 47 aulas.

## Duas coisas nesta pasta

| | O quê | Onde |
|---|---|---|
| **Material de estudo** | Uma nota por aula: resumo, código de referência, armadilhas e a entrega | `Modulo_N/NN-slug.md` |
| **Código executável** | Uma demonstração por aula, que roda e confere sozinha | `Modulo_N/NN-slug.php` |
| **Índice das aulas** | Metadado das 47 aulas: título, URL, status, comando, o que demonstra | [aulas.json](aulas.json) |
| **Divergências** | Onde o material e a realidade não batem | [DIVERGENCIAS.md](DIVERGENCIAS.md) |
| **O projeto** | O **CRM de produto** — código real, que roda e é testado | [crm-produto/](crm-produto/) |

O material da aula fica em `.md` (é assim que a própria plataforma guarda: todo
`lesson-id` do site termina em `.md`). A exceção é
[Modulo_1/02-revisao-ativa.php](Modulo_1/02-revisao-ativa.php), que é gabarito
executável de 9 exercícios e roda como PHP.

Cada nota abre com título, link da aula e status `assistida / anotada / praticada`,
e fecha com uma seção **Aplicar no CRM de produto**.

### Por que o metadado saiu do PHP

Cada `.php` de aula abria com um docblock de ~12 linhas repetindo título, URL, status e
comando — o mesmo dado que já estava no `.md` e neste índice, em três lugares que
saíam de sincronia. Isso agora mora em [aulas.json](aulas.json) (formato descrito em
[aulas.schema.json](aulas.schema.json)), e cada `.php` abre com duas linhas apontando
para lá. Os comentários `//` que explicam **uma linha específica** continuam no PHP, de
propósito: fora do lugar onde a linha está, eles não querem dizer nada.

```bash
# o que ainda não foi praticado, e por quê
php -r '$d=json_decode(file_get_contents("aulas.json"),true);
foreach($d["aulas"] as $a) if(!$a["status"]["praticada"])
  echo $a["arquivos"]["codigo"]." — ".$a["status"]["observacao"]."\n";'
```

## O projeto — [crm-produto/](crm-produto/)

Evolução do `mini-crm` do PHPIAN (que fica intocado lá, como registro). Estado atual,
com `composer quality` passando limpo:

```
crm-produto/
├── src/
│   ├── Domain/          entidades, enums, exceções e interfaces de repositório
│   ├── Application/     casos de uso (CadastrarCliente, CadastrarContato, ...)
│   ├── Infrastructure/  implementações (em memória, e-mail em log, CSV)
│   └── HealthCheck.php
└── tests/               espelha src/, namespace Tests\
```

```bash
docker start crm-mysql   # Módulos 5 a 8 precisam do banco
php rodar-todas.php      # as 47 aulas, com placar
php rodar-todas.php 5    # só o Módulo 5

cd crm-produto
composer quality         # estilo + PHPStan level 5 + PHPUnit — portão de cada aula
```

**Última execução de [rodar-todas.php](rodar-todas.php): 47 aulas · 47 ok · 0 falhas ·
704 asserções.**

Cada aula é código que **afirma** coisas sobre o projeto e falha alto se elas deixarem
de ser verdade. Foi assim que apareceram as divergências do material e quatro bugs que
só o banco real revelava — estão em [DIVERGENCIAS.md](DIVERGENCIAS.md).

**Última execução:** estilo 0 problemas · PHPStan level 5 sem erros · 25 testes,
48 asserções.

Pendente: implementações **PDO** dos repositórios (dependem de um banco disponível) e
o remetente de e-mail real (Módulo 6).

## Índice das aulas

### Módulo 1 — Mapa do intermediário e revisão ativa

- [01] [Modulo_1/01-salto-do-phpian.md](Modulo_1/01-salto-do-phpian.md) — O salto do PHPIAN: o que muda no dia a dia
- [02] [Modulo_1/02-revisao-ativa.php](Modulo_1/02-revisao-ativa.php) — Revisão ativa: PDO, auth por sessão e OOP intro
- [03] [Modulo_1/03-produto-em-camadas.md](Modulo_1/03-produto-em-camadas.md) — Como um produto PHP "de verdade" se parece
- [04] [Modulo_1/04-briefing-crm-produto.md](Modulo_1/04-briefing-crm-produto.md) — Briefing do CRM de produto
- [05] [Modulo_1/05-ambiente-profissional.md](Modulo_1/05-ambiente-profissional.md) — Ambiente profissional: PHP 8.3, Composer no fluxo diário

### Módulo 2 — OOP de verdade (sem framework)

- [01] [Modulo_2/01-classes-tipadas.md](Modulo_2/01-classes-tipadas.md) — Classes, propriedades tipadas e métodos com intenção clara
- [02] [Modulo_2/02-composicao-vs-heranca.md](Modulo_2/02-composicao-vs-heranca.md) — Encapsulamento, composição vs herança: quando usar cada um
- [03] [Modulo_2/03-interfaces-contratos.md](Modulo_2/03-interfaces-contratos.md) — Interfaces e contratos: o "porquê" antes do "como"
- [04] [Modulo_2/04-namespaces-psr4.md](Modulo_2/04-namespaces-psr4.md) — Namespaces e autoload PSR-4 com Composer
- [05] [Modulo_2/05-excecoes-dominio.md](Modulo_2/05-excecoes-dominio.md) — Exceções de domínio: erros que o produto entende
- [06] [Modulo_2/06-refatorar-dominio-crm.md](Modulo_2/06-refatorar-dominio-crm.md) — Refatorar o Mini CRM: extrair Modelos e Serviços

### Módulo 3 — MVC próprio (ainda sem Laravel)

- [01] [Modulo_3/01-ciclo-http-camadas.md](Modulo_3/01-ciclo-http-camadas.md) — O ciclo HTTP revisitado, com camadas
- [02] [Modulo_3/02-front-controller-rotas.md](Modulo_3/02-front-controller-rotas.md) — Front Controller e roteamento simples
- [03] [Modulo_3/03-controllers-services.md](Modulo_3/03-controllers-services.md) — Controllers finos, Services gordos
- [04] [Modulo_3/04-views-layouts.md](Modulo_3/04-views-layouts.md) — Views PHP: layouts e partials
- [05] [Modulo_3/05-validacao-formularios.md](Modulo_3/05-validacao-formularios.md) — Validação centralizada e feedback de formulário
- [06] [Modulo_3/06-middleware-leve.md](Modulo_3/06-middleware-leve.md) — Middleware leve: auth, CSRF, "só admin"

### Módulo 4 — API REST + JSON

- [01] [Modulo_4/01-recursos-verbos-status.md](Modulo_4/01-recursos-verbos-status.md) — Recursos, verbos e status HTTP na prática
- [02] [Modulo_4/02-json-contratos.md](Modulo_4/02-json-contratos.md) — JSON de entrada e saída (contratos estáveis)
- [03] [Modulo_4/03-versionamento-api-v1.md](Modulo_4/03-versionamento-api-v1.md) — Versionamento simples (/api/v1)
- [04] [Modulo_4/04-paginacao-filtros-erros.md](Modulo_4/04-paginacao-filtros-erros.md) — Paginação, filtros e erros padronizados
- [05] [Modulo_4/05-consumir-api-no-painel.md](Modulo_4/05-consumir-api-no-painel.md) — Consumir a própria API no painel
- [06] [Modulo_4/06-documentar-endpoints.md](Modulo_4/06-documentar-endpoints.md) — Documentar endpoints (mínimo legível para humano)

### Módulo 5 — Autenticação e autorização sérias

- [01] [Modulo_5/01-limites-sessao-vs-token.md](Modulo_5/01-limites-sessao-vs-token.md) — Sessão vs token: limites de cada um
- [02] [Modulo_5/02-access-refresh-tokens.md](Modulo_5/02-access-refresh-tokens.md) — Access token + refresh token
- [03] [Modulo_5/03-papeis-permissoes.md](Modulo_5/03-papeis-permissoes.md) — Papéis e permissões: admin, vendedor, leitura
- [04] [Modulo_5/04-reset-senha-email.md](Modulo_5/04-reset-senha-email.md) — Reset de senha por e-mail
- [05] [Modulo_5/05-rate-limit-rotas.md](Modulo_5/05-rate-limit-rotas.md) — Rate limit em rotas sensíveis
- [06] [Modulo_5/06-auditoria.md](Modulo_5/06-auditoria.md) — Auditoria: quem fez o quê

### Módulo 6 — Recursos de produto

- [01] [Modulo_6/01-upload-seguro.md](Modulo_6/01-upload-seguro.md) — Upload seguro de anexos
- [02] [Modulo_6/02-filas-jobs.md](Modulo_6/02-filas-jobs.md) — Filas e jobs (e-mail, relatórios)
- [03] [Modulo_6/03-logs-estruturados.md](Modulo_6/03-logs-estruturados.md) — Logs estruturados
- [04] [Modulo_6/04-notificacoes-lembretes.md](Modulo_6/04-notificacoes-lembretes.md) — Notificações e lembretes (agenda do CRM)
- [05] [Modulo_6/05-exportacao-csv-pdf.md](Modulo_6/05-exportacao-csv-pdf.md) — Exportação CSV e PDF
- [06] [Modulo_6/06-soft-delete-busca.md](Modulo_6/06-soft-delete-busca.md) — Soft delete e busca

### Módulo 7 — Produção: config, ambientes e deploy

- [01] [Modulo_7/01-env-secrets-config.md](Modulo_7/01-env-secrets-config.md) — .env, secrets e config por ambiente
- [02] [Modulo_7/02-migracoes-banco.md](Modulo_7/02-migracoes-banco.md) — Migrações de banco
- [03] [Modulo_7/03-staging-vs-producao.md](Modulo_7/03-staging-vs-producao.md) — Staging vs produção
- [04] [Modulo_7/04-deploy-https.md](Modulo_7/04-deploy-https.md) — Deploy (VPS ou hospedagem avançada) + HTTPS
- [05] [Modulo_7/05-backup-health-rollback.md](Modulo_7/05-backup-health-rollback.md) — Backup, health check e rollback
- [06] [Modulo_7/06-observabilidade-minima.md](Modulo_7/06-observabilidade-minima.md) — Observabilidade mínima

### Módulo 8 — Monetização leve e projeto final

- [01] [Modulo_8/01-planos-limites.md](Modulo_8/01-planos-limites.md) — Planos, limites e "access granted"
- [02] [Modulo_8/02-checkout-webhooks.md](Modulo_8/02-checkout-webhooks.md) — Checkout conceitual, gateway e webhooks
- [03] [Modulo_8/03-dominio-email-suporte.md](Modulo_8/03-dominio-email-suporte.md) — Domínio, e-mail profissional e suporte
- [04] [Modulo_8/04-hardening-final.md](Modulo_8/04-hardening-final.md) — Hardening final
- [05] [Modulo_8/05-projeto-final-rubrica.md](Modulo_8/05-projeto-final-rubrica.md) — Projeto final: rubrica de entrega
- [06] [Modulo_8/06-roadmap-phppro.md](Modulo_8/06-roadmap-phppro.md) — Roadmap: o que vem no PHPPRO

---

Módulos 1 e 2 completos (11 aulas). Próxima: Módulo 3 — MVC próprio.
