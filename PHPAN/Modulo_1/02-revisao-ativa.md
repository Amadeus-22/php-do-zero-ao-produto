# Aula 02 — Revisão ativa: PDO, auth por sessão e OOP intro

**Código:** [02-revisao-ativa.php](02-revisao-ativa.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/02-revisao-ativa)

## A ideia

Esta aula não ensina nada novo — é um **checkpoint**. Resolver os 9 exercícios sem
consultar tutorial. Se travar, o problema não é o PHPAN: é base do PHPIAN que ainda não
sedimentou, e vale reforçar antes de empilhar complexidade.

Regra da aula: errou mais de 3 dos 9 → revisar o PHPIAN antes de seguir.

## Por que este arquivo é `.php` e não `.md`

É o único do curso assim. O conteúdo aqui **é** código executável — os gabaritos são
funções que rodam, não trechos ilustrativos. Colocá-los em bloco de markdown os tornaria
não verificáveis.

## Os três pilares

**PDO** — `prepare()` + parâmetro nomeado. O ponto não é decorar a sintaxe, é saber
explicar *por que* isso evita SQL injection: o valor viaja separado da instrução, então
nunca é interpretado como comando.

**Sessão** — `password_verify` + `session_regenerate_id(true)`. O segundo é o que
impede session fixation, e é o mais esquecido.

**OOP** — propriedade `private` por padrão, acesso por método. Não é preferência
estética: é o que protege a invariante.

## O que rodou aqui

Bloco 3 (OOP) e o `logout()` completo foram executados: 3.1, 3.3, e o logout removendo o
arquivo de sessão, esvaziando `$_SESSION` e encerrando a sessão.

Os blocos de PDO (1.x e 2.1) precisam de banco. Quando o MySQL entrou no projeto
(Módulo 7), o mesmo código passou a rodar de verdade — mas nesta aula ele fica como
gabarito de leitura.

## Um erro meu que virou lição

Montei o teste do `logout()` com um `echo` **antes** do `session_start()`. Sem sessão
iniciada, o teste passou dando "OK" que não valia nada. O `_aula.php` hoje liga
`ob_start()` no início justamente para que cabeçalho e cookie ainda possam ser enviados
depois de imprimir texto.

## Onde cada exercício reaparece

| Exercício | Vira o quê |
|---|---|
| 1.1 prepared statement | `RepositorioDeClientesPdo` (Módulo 3/7) |
| 1.3 transação | reserva de lembretes e webhook (Módulos 6 e 8) |
| 2.1 `session_regenerate_id` | `Auth\Sessao::entrar()` (Módulo 5) |
| 2.3 logout completo | `Auth\Sessao::sair()` (Módulo 5) |
| 3.1 array → classe | `Domain\Cliente\Cliente` (Módulo 2) |
| 3.3 invariante protegida | validação na fábrica (Módulo 2) |
