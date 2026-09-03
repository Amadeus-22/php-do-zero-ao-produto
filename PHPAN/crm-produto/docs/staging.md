# Staging vs produção

**Paridade é o que faz staging valer.** Staging rodando PHP 8.1 enquanto produção roda
8.3, ou SQLite contra MySQL, não é staging — é outro ambiente de dev, e o bug que só
aparece em produção fica garantido.

## O que muda

| Muda | Exemplo |
|---|---|
| Valores do `.env` | banco, `APP_URL`, chaves em sandbox |
| Volume de dado | staging com dado fake ou **anonimizado** |
| Quem acessa | staging com Basic Auth ou IP allowlist; produção pública |

## O que NÃO muda

Código (mesmo commit/tag), versão de PHP e extensões, estrutura de infra. Se staging
testa código diferente, o teste mente.

## Arquivos de ambiente

```
.env              # local do dev, nunca vai para o servidor
.env.staging      # vive só no servidor de staging
.env.production   # vive só no servidor de produção
.env.example      # vai para o Git, documenta as chaves
```

Nenhum dos três primeiros é commitado. `.gitignore` tem `.env`, `.env.*` e
`!.env.example`.

## `APP_ENV` muda o comportamento de erro

Em produção, erro vai para o log, não para a tela — stack trace na tela entrega caminho
de arquivo, versão de biblioteca e às vezes string de conexão. Em staging você quer ver
o erro completo. É o que `public/index.php` faz no boot.

## Isolamento de dado

Staging **nunca** aponta para o banco de produção. Se precisar de dado realista, use
dump anonimizado:

```sql
UPDATE clientes SET email = CONCAT('cliente', id, '@example.test'), telefone = '11999990000';
UPDATE usuarios SET senha_hash = '$2y$10$hashFalsoParaStagingSomente...';
```

Dado real de cliente em staging é risco de LGPD e vazamento acidental.

## Estado deste projeto

Ambiente único (local, Docker) — staging e produção **ainda não existem**. Quando
existirem, a paridade a garantir é: PHP 8.3+, MySQL 8, mesmas extensões, mesmo commit.
Isso está declarado aqui em vez de fingir que já foi feito.
