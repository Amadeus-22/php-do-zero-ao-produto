# Aula 01 — O ciclo HTTP revisitado, com camadas

**Código executável:** [01-ciclo-http-camadas.php](01-ciclo-http-camadas.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/01-ciclo-http-camadas)

## A ideia

O problema **não é o PHP, é a ausência de fronteiras**. Sem camadas, toda página
repete checagem de sessão, conexão e validação — e a regra "e-mail não duplica" fica
em quatro arquivos ligeiramente diferentes.

A requisição passa a atravessar peças de responsabilidade única:

```
Requisição → Front Controller → Router → Controller → Service → Repository → Model
```

E volta: Repository devolve Models, Service devolve dado pronto, Controller escolhe
HTML ou JSON.

## Por que essa divisão e não outra

A pergunta que decide a camada:

| Pergunta | Camada |
|---|---|
| Depende de `$_GET`/`$_POST`/cabeçalho? | Controller |
| É regra do negócio, web ou API? | Service |
| É SQL / PDO? | Repository |
| É só dado, sem comportamento? | Model |
| É só HTML? | View |

O teste real: **a regra sobrevive se a chamada vier de um cron?** Se sim, é Service.

## O ganho concreto

`ClienteService` é o mesmo objeto para o painel e para a API. No arquivo `.php` isso é
provado: a API recusa e-mail duplicado com 409 **sem uma linha de regra nova**.

## O que quebra sem isso

- Controller chamando PDO "só dessa vez" — em duas semanas metade do sistema não passa
  mais pelo Repository.
- Service que lê `$_POST` — deixa de ser reaproveitável pela API.
- Camada vazia que só repassa — tão ruim quanto não ter camada.
- Model x Service: Model é o dado; Service é o comportamento sobre o dado.
