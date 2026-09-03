# Aula 02 — Filas e jobs (e-mail, relatórios)

**Código:** [02-filas-jobs.php](02-filas-jobs.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/02-filas-jobs)

## A ideia

Mandar e-mail dentro do request de "criar cliente" tem dois problemas: o usuário espera
o SMTP responder, e você precisa decidir **na hora** se uma falha de envio derruba a
criação do cliente. Nenhuma das duas respostas é boa.

A fila desfaz o dilema: a ação principal **registra a intenção** e devolve a resposta
imediatamente. Um worker separado executa depois, com direito a retry.

## Fila de tabela, sem Redis

O padrão é o mesmo com ou sem broker: `job = tipo + payload`, um `status`, e um processo
que consome. Com o volume do CRM, uma tabela resolve — e a lição do Módulo 2 vale: se
Redis entrar depois, troca a implementação sem mexer em quem despacha.

## Os quatro conceitos que valem para qualquer fila

**Status:** `pendente → processando → concluido | falhou`.

**Idempotência.** Se o job rodar duas vezes (retry, worker duplicado), o resultado não
pode ser "dois e-mails". É responsabilidade do **handler**: o de relatório checa se o
arquivo já existe antes de gerar.

**Retry com backoff.** `30 * 2^tentativas` — 60s, 120s, 240s. Retentar em loop imediato
contra um SMTP fora do ar só multiplica a falha.

**Dead-letter.** Depois de 5 tentativas o job vira `falhou` e para. Sem limite, um
payload inválido fica retentando para sempre e polui a fila.

## `FOR UPDATE SKIP LOCKED`

```sql
SELECT * FROM jobs WHERE status = 'pendente' AND disponivel_em <= NOW()
ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED
```

`FOR UPDATE` trava a linha; `SKIP LOCKED` faz o segundo worker **pular** a linha travada
em vez de esperar por ela. Sem isso, dois workers pegam o mesmo job e o e-mail sai duas
vezes.

## O que quebra sem isso

- **Worker travado** num job com chamada externa sem timeout: a fila inteira para.
- **Worker não está rodando em produção** — é o esquecimento mais comum. Sem
  `bin/worker.php` de pé (supervisor/systemd, Módulo 7), os jobs só se acumulam como
  `pendente` e ninguém percebe até alguém reclamar do e-mail que não chegou.
