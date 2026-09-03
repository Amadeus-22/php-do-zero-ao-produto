# Aula 05 — Consumir a própria API no painel

**Código executável:** [05-consumir-api-no-painel.php](05-consumir-api-no-painel.php) · **Tela:** `/painel` · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/05-consumir-api-no-painel)

## A ideia

O melhor teste de uma API é ser **o primeiro cliente dela**. Se o seu próprio painel
tem dificuldade de consumir o contrato, quem integrar de fora terá mais.

A tela `/painel` lista e cria clientes só com `fetch`, sem recarregar a página.

## A ponte de autenticação (e por que é honesta declarar isso)

Antes do Módulo 5 não existe token. Duas saídas: emitir token no login, ou proteger
`/api/v1` com a **mesma sessão** do painel + CSRF nas mutações. A segunda é aceitável
como ponte — **desde que documentada**, que é o que a aula pede e o que `docs/api.md`
registra.

Por isso o JS usa `credentials: 'same-origin'` (manda o cookie de sessão) e
`X-CSRF-TOKEN`. Quando o Bearer chegar, sai o cookie e entra `Authorization`.

## As três defesas do cliente JS

**1. `escapeHtml` antes do `innerHTML`.** Montar HTML por concatenação com dado do
servidor é XSS mesmo quando o dado veio da sua própria API — basta um nome de cliente
com `<script>`.

**2. `res.json().catch(() => ({}))`.** Um `500` pode devolver **HTML** de erro do PHP,
não JSON. Sem o catch, o parse estoura e o usuário vê um erro de sintaxe em vez da
mensagem.

**3. Erro como exceção com contexto.**

```js
throw Object.assign(new Error(msg), { status: res.status, payload });
```

`fetch` **não rejeita** em 4xx/5xx — só em falha de rede. Sem checar `res.ok`
explicitamente, um 422 seguiria como se tivesse dado certo.

## O que o envelope faz por essa tela

`const { data } = await api(...)` e `payload?.error?.message` só funcionam porque o
formato é o mesmo em todo endpoint. É o retorno prático da aula 2.
