# CRM de produto

Projeto do curso PHPAN: um CRM em PHP 8.3+, **sem framework**, construído camada a
camada — domínio, aplicação, infraestrutura e HTTP.

## Quem usa e que dor resolve

Vendedor autônomo ou equipe pequena que hoje controla clientes em planilha: perde
histórico de contato, esquece follow-up e não sabe o que foi combinado com quem. O CRM
guarda cliente, contato e atividade num lugar só, com lembrete de retorno.

## Instalar

Requisitos: PHP 8.3+, Composer, MySQL 8.

```bash
composer install
cp .env.example .env      # preencha DB_* e WEBHOOK_SECRET
php bin/migrate.php up
php bin/seed-usuarios.php # cria um usuário por papel (só fora de produção)
php -S localhost:8000 -t public
```

Sem MySQL local, sobe um em container:

```bash
docker run -d --name crm-mysql \
  -e MYSQL_ROOT_PASSWORD=raiz -e MYSQL_DATABASE=crm_produto \
  -e MYSQL_USER=crm -e MYSQL_PASSWORD=crm \
  -p 127.0.0.1:3307:3306 mysql:8
```

Acesse `/login` — `admin@exemplo.com` / `senha-de-estudo`.

## Processos que precisam estar rodando

```bash
php bin/worker.php              # fila (e-mail, relatórios) — supervisor em produção
php bin/verificar-lembretes.php # cron a cada 5 min
php bin/limpar-tentativas.php   # cron diário
```

Sem o worker de pé, os jobs só se acumulam.

## Qualidade

```bash
composer quality   # estilo + PHPStan level 5 + PHPUnit + composer audit
```

Os testes de integração usam o banco; se ele não estiver de pé, são **pulados**, não
falham.

## Estrutura

```
src/Domain/          entidades, enums, exceções, contratos de repositório
src/Application/     casos de uso (não conhecem HTTP)
src/Infrastructure/  implementações (PDO, e-mail, CSV)
src/Http/            Request, Response, Router, middlewares, controllers
src/Auth/            sessão, tokens, reset de senha
src/Billing/         planos, limites, webhook de pagamento
views/               HTML (layout, partials, páginas, erros)
migrations/          schema em .sql puro (up/down)
bin/ scripts/ deploy/  CLI, operação e configuração de servidor
docs/                api, runbook, checklists, decisões
```

Cada linguagem no seu arquivo — ver [docs/separacao-de-arquivos.md](docs/separacao-de-arquivos.md).

## Documentação

| Arquivo | O que é |
|---|---|
| [docs/api.md](docs/api.md) | contrato da API v1 |
| [docs/runbook.md](docs/runbook.md) | o que fazer quando quebrar |
| [docs/checklist-deploy.md](docs/checklist-deploy.md) | promoção staging → produção |
| [docs/hardening.md](docs/hardening.md) | checklist de segurança e pendências |
| [docs/rubrica-final.md](docs/rubrica-final.md) | entrega do projeto, com evidência |
