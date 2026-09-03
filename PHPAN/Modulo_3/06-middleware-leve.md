# Aula 06 — Middleware leve: auth, CSRF, "só admin"

**Código executável:** [06-middleware-leve.php](06-middleware-leve.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/06-middleware-leve)

## A ideia

Middleware roda **entre o Router e o Controller**, com poder de decisão. Isso elimina o
`if (!logado) { redirect; }` copiado no topo de cada método de cada Controller.

O contrato inteiro cabe numa linha:

```php
public function handle(Request $request): ?Response;
```

- `null` → segue para o próximo middleware, ou para o Controller.
- `Response` → a cadeia **para ali**; o Controller nem é chamado.

## Por que não PSR-15 agora

PSR-15 tem `next(): Handler` encadeado, `process()`, pilha de handlers. É útil em
projeto grande e é complexidade que não se paga no tamanho atual do CRM. O contrato
acima resolve o mesmo problema com uma interface de um método.

## A ordem no array de rota não é estética

`AdminMiddleware` lê `$_SESSION['papel']` **assumindo que a sessão já existe** — quem
garante isso é o `AuthMiddleware`, que roda antes. Invertido, um visitante anônimo
recebe 403 ("acesso restrito") em vez do redirect para login: comportamento confuso em
vez de resultado claro.

## CSRF: por que só em POST/PUT/PATCH/DELETE

`GET` não deve alterar estado (Módulo 4, aula 1). Validar token em `GET` só criaria
atrito sem ganho de segurança.

## Por que `hash_equals` e não `===`

Comparação de string com `===` retorna assim que encontra o primeiro byte diferente — o
tempo de resposta vaza informação sobre quantos caracteres do token estavam certos.
`hash_equals` compara em **tempo constante**. Mesma razão do `password_verify`.

E `bin2hex(random_bytes(32))`: 32 bytes de entropia criptográfica. `uniqid()` ou `rand()`
são previsíveis — um token adivinhável não é token.

## O que quebra sem isso

- Esquecer CSRF na rota de **remover** — é a mais perigosa de forjar e a mais esquecida,
  porque a atenção fica toda no login.
- Middleware pesado: recarregar o usuário inteiro do banco a cada requisição só para
  saber se está logado. Checar a sessão resolve 90% dos casos nesta fase.
