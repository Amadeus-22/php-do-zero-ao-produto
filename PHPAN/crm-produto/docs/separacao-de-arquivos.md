# Cada linguagem no seu arquivo

Regra do projeto: **PHP em `.php`, SQL em `.sql`, HTML em view, JS em `.js`, CSS em
`.css`, configuração em `.json`/`.neon`/`.env`.** Um arquivo que mistura duas
linguagens esconde as duas.

Este documento existe porque o projeto **estava** violando isso em três pontos, e a
correção vale para tudo que vier depois (Módulos 5 a 8).

## O que estava errado, e por quê

### 1. Schema em heredoc dentro de PHP

```php
// migrations/..._create_clientes_table.php  — ANTES
$pdo->exec(<<<SQL
    CREATE TABLE clientes ( ... )
SQL);
```

Problemas concretos: o editor não destaca sintaxe SQL dentro da string, o `diff` do Git
mistura mudança de schema com mudança de código PHP, e nenhuma ferramenta de banco
consegue ler o arquivo.

**Agora:** cada migração é um par de arquivos SQL de verdade.

```
migrations/20260831_0001_create_clientes_table.up.sql
migrations/20260831_0001_create_clientes_table.down.sql
sql/migrations_table.sql
```

`bin/migrate.php` continua PHP porque o que ele faz **é** lógica: lê o que já rodou,
ordena, executa e trata erro. Ele carrega o SQL com `Support\Sql`.

> **Onde a linha foi traçada:** o SQL das *queries* segue dentro de
> `RepositorioDeClientesPdo`. Ali o SQL e o `execute([...])` são a mesma unidade — os
> parâmetros nomeados da query casam com as chaves do array PHP, e separá-los criaria
> dois arquivos que só fazem sentido juntos. A regra é **DDL (estrutura) fora do PHP;
> DML (consulta) junto do método que a executa**.

### 2. HTML dentro de classe PHP

```php
// src/Http/Router.php — ANTES
return Response::html('<h1>404 — página não encontrada</h1>', 404);
```

Marcação é apresentação; roteador é infraestrutura. Para estilizar a página de 404 era
preciso abrir o roteador — e as páginas de 403, 404 e 419 não tinham nada em comum
entre si, cada uma escrita à mão num arquivo diferente.

**Agora:** `views/erros/{403,404,419}.php` e a fábrica `Http\PaginaDeErro`.

### 3. JSON como banco de dados

```php
// src/Infrastructure/Cliente/RepositorioDeClientesEmArquivo.php — REMOVIDO
file_put_contents($this->caminho, json_encode($linhas, ...));
```

Isso existiu como muleta enquanto não havia banco. Foi uma decisão ruim de manter
depois que o MySQL entrou, por dois motivos:

- **Dado de negócio não mora em JSON no disco.** Sem transação, sem índice, sem
  constraint — a unicidade de e-mail dependia de um `foreach` em PHP, e não do
  `UNIQUE KEY` do banco.
- **Ele mentia sobre o contrato.** Descartava o campo `telefone` silenciosamente: o
  `POST` respondia 201 e o dado sumia. O bug só apareceu quando o MySQL entrou.

**Agora:** `RepositorioDeClientesPdo` é a única persistência da aplicação.
`RepositorioDeClientesEmMemoria` continua existindo, mas é **duplo de teste** — por
isso não aparece no `Container`; os testes o injetam com `Container::usar()`.

## Onde JSON continua certo

- **`Http\Request` / `Http\Response`** — JSON ali é o **formato do protocolo HTTP**,
  não armazenamento. Decodificar corpo de requisição e serializar resposta é a função
  dessas classes.
- **`composer.json`** — configuração, no formato que a ferramenta exige.

A pergunta que separa os casos: *o JSON está atravessando a fronteira do sistema
(entrada/saída), ou está sendo usado para guardar estado?* O primeiro é legítimo; o
segundo é banco de dados improvisado.

## Regra para os Módulos 5 a 8

| O que vier | Onde mora |
|---|---|
| Tabela nova (tokens, jobs, auditoria, lembretes, planos) | `migrations/*.up.sql` + `*.down.sql` |
| Consulta de repositório | dentro do método, no repositório PDO |
| Tela nova, página de erro, e-mail em HTML | `views/` |
| Comportamento de tela | `public/assets/js/*.js` |
| Estilo | `public/assets/*.css` |
| Segredo e diferença de ambiente | `.env` (+ `.env.example` versionado) |
| Log estruturado (Módulo 6) | `.jsonl` em `var/logs/` — JSON como **formato de saída**, uma linha por evento, nunca lido de volta como banco |
