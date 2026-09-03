# Aula 04 — Paginação, filtros e erros padronizados

**Código executável:** [04-paginacao-filtros-erros.php](04-paginacao-filtros-erros.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/04-paginacao-filtros-erros)

## A ideia

Lista sem paginação funciona com 20 clientes e derruba o servidor com 200 mil. E sem
contrato de erro, o front adivinha. As duas coisas se resolvem cedo porque mudá-las
depois é breaking change (aula 3).

## O `meta` e por que ele existe

```json
"meta": { "page": 1, "per_page": 20, "total": 137, "total_pages": 7 }
```

Sem `total`, o front não desenha paginação — ele não tem como saber se há próxima
página a não ser pedindo e vendo se veio vazio.

## Os três limites que não são detalhe

```php
page:    max(1, (int) ($query['page'] ?? 1))
perPage: min(100, max(1, (int) ($query['per_page'] ?? 20)))
```

- `max(1, ...)` — `page=0` ou `page=-5` viraria `offset` negativo.
- `min(100, ...)` — sem teto, `per_page=999999` é um pedido de "me devolva a base
  inteira", e o servidor obedece até cair.
- O cast `(int)` faz `per_page=abc` virar `0`, que o `max(1, ...)` salva.

O `.php` desta aula dispara os três casos hostis e mostra o valor que sobrou no `meta`.

## Onde a paginação acontece — e por quê

No `RepositorioDeClientesPdo` a paginação acontece **no SQL**: `WHERE` monta o filtro
e `LIMIT`/`OFFSET` recortam a página. Paginar em PHP depois de um `SELECT *` traria a
tabela inteira para a memória e descartaria 99% dela — custo igual ao de não paginar.

O duplo em memória (usado nos testes) filtra em PHP porque a "tabela" dele é um array
que já está na memória — ali não há o que otimizar.

## Códigos de erro do projeto

`validation_failed` (422) · `not_found` (404) · `unauthorized` (401) · `forbidden` (403)
· `conflict` (409) · `rate_limited` (429) · `server_error` (500).

O front trata por `code`, nunca pelo texto da `message` — texto muda com revisão de
copy, `code` é contrato.

## O que quebra sem isso

- Erro HTML do PHP misturado numa resposta que deveria ser JSON: o cliente faz
  `res.json()` e recebe exceção de parse em vez da mensagem de erro.
