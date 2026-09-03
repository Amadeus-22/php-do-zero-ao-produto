# Aula 03 — Controllers finos, Services gordos

**Código executável:** [03-controllers-services.php](03-controllers-services.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/03-controllers-services)

## A ideia

Regra de bolso: se o método do Controller passa de ~10 linhas, ou tem um `if` que
decide algo **do negócio** (não da requisição), essa lógica é do Service.

| Camada | Sabe | Não sabe |
|---|---|---|
| Controller | `Request`, `Response`, formato | O que é SQL; se e-mail pode duplicar |
| Service | Regra de negócio | O que é `$_POST` ou `Response` |
| Repository | PDO | Regra de negócio |

## O motivo de o Service não receber `Request`

Passar `Request` para o Service acopla a regra ao HTTP. No momento em que um script de
importação CSV ou um comando de terminal precisar da mesma regra, não dá para usar.

O Service recebe **array, escalar ou objeto de domínio** — e devolve dado ou lança
exceção de domínio. Quem traduz isso em status HTTP é o Controller.

O `.php` desta aula verifica isso lendo o código-fonte com `php_strip_whitespace()`:
nenhum `$_POST`, `$_GET`, `$_SESSION` ou `Response::` dentro do `ClienteService`.

## Repository devolvendo objeto, não array

`$linha['nome']` espalhado pelo Controller e pela View significa: sem checagem de tipo,
sem autocomplete, e um `typo` só descoberto em produção. O Repository devolve `Cliente`.

## Sobre montar o Service "na mão" no construtor

A aula monta `new ClienteService(new ClienteRepository(...))` sem container de DI, e
está certo para este tamanho. No projeto isso virou `Container::clienteService()` — não
é um container de injeção, é só **o lugar único onde se decide qual implementação
concreta o app usa**. Quando o banco entrou, trocar o duplo pelo
`RepositorioDeClientesPdo` foi **uma linha** ali — nenhum Controller, Service ou
entidade mudou. Era exatamente a promessa da interface, cobrada na prática.

## O que quebra sem isso

- Regra no Controller: some no dia em que web e API precisarem dela.
- "Service" que só repassa para o Repository: camada sem motivo — adicione quando a
  regra aparecer, não antes.
