# Aula 05 — Validação centralizada e feedback de formulário

**Código executável:** [05-validacao-formularios.php](05-validacao-formularios.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/05-validacao-formularios)

## A ideia

Duas coisas diferentes que costumam ser chamadas de "validação":

| | Validação de formato | Regra de negócio |
|---|---|---|
| Exemplo | "e-mail tem formato de e-mail" | "esse e-mail já está cadastrado" |
| Precisa de I/O? | Não | Sim (consulta) |
| Onde mora | `Validator` | `Service` |
| Como falha | Erro por campo | Exceção de domínio |

Misturar as duas funciona no começo e vira bagunça quando a regra precisa de uma
consulta que o `Validator` não tem como fazer de forma limpa.

## Por que cada método devolve `self`

```php
->required('nome', '...')
->max('nome', 120, '...')
->email('email', '...')
```

Encadeamento faz as regras de um formulário serem lidas de cima para baixo, como uma
especificação. O ganho é legibilidade, não economia de linha.

## Por que uma classe `ClienteValidator` própria

É a **fonte única**. O Controller web e o Controller da API (Módulo 4) chamam a mesma
`ClienteValidator::validar()`. Sem isso, a regra muda num lugar e fica desatualizada nos
outros dois — e ninguém percebe até o bug.

## A ordem importa por custo

Formato primeiro (barato, em memória), negócio depois (consulta). Não vale bater no
banco para descobrir duplicidade de um formulário que nem tem e-mail preenchido.

## Old input: a parte que parece detalhe e não é

Se o usuário erra uma letra no e-mail e o formulário volta em branco, ele redigita
tudo. Na terceira vez, desiste do seu CRM. Por isso o Controller devolve `'antigo' =>
$dados` junto com os erros, e a view repopula os campos.

## O que quebra sem isso

- Validar só no JavaScript: qualquer requisição forjada passa direto. JS é conforto de
  UX; validação de verdade é no servidor.
- Duplicidade dentro do `Validator`: acopla I/O a uma classe que deveria ser síncrona.
