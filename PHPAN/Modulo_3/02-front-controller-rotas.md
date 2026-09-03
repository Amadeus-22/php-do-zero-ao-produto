# Aula 02 — Front Controller e roteamento simples

**Código executável:** [02-front-controller-rotas.php](02-front-controller-rotas.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/02-front-controller-rotas)

## A ideia

**Uma porta só.** Toda requisição entra por `public/index.php`; o resto do código fica
fora da raiz pública. Isso mata o problema do PHPIAN de ter um `.php` acessível por URL
para cada tela — se o arquivo não é servido, ele não é atacável.

O `Router` é a lista de rotas (`método + padrão + handler`) percorrida até a primeira
que casa.

## Por que normalizar no `Request`

```php
rtrim($path, '/') ?: '/'
```

Decidir **uma vez, aqui**, que `/clientes` e `/clientes/` são a mesma rota. A
alternativa é tratar isso em cada rota — e esquecer em uma delas, gerando um 404 sem
motivo aparente.

O mesmo vale para `strtoupper($method)` e para o corpo: se o `Content-Type` é JSON, o
`Request` já decodifica; o Controller não precisa saber a diferença.

## O truque do `...$params`

```php
return ($route['handler'])($request, ...$params);
```

`$params` é **associativo** (`['id' => '3']`). O spread de um array associativo em PHP
vira **argumentos nomeados**. Consequência prática: o parâmetro do handler precisa se
chamar exatamente como o `{placeholder}` da rota.

## A armadilha que mais custa caro

`match()` para na **primeira** rota que casa. Com `/clientes/{id}` declarada antes de
`/clientes/novo`, a URL `/clientes/novo` cai em `show` com `id = "novo"`.

**Regra:** rota específica primeiro, rota com parâmetro por último. O `.php` desta aula
reproduz o bug de propósito para você ver acontecer.

## Por que `resolver()` além de `dispatch()`

`dispatch()` envia a resposta (`echo` + `header`) — impossível de testar. `resolver()`
**devolve** a `Response`. É por isso que existem 10 testes de rota rodando sem subir
servidor nenhum.
