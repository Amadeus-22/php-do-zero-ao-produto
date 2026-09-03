# PHPIAN — Iniciante

Curso `phpian` de `cursos.asllanmaciel.com.br` — 8 módulos, 40 aulas, 12h48 de material.
Concluído; acesso até 03/08/2027.

## O que tem nesta pasta

| | O quê | Onde |
|---|---|---|
| **Índice das aulas** | As 40 aulas: título, duração, resumo, seções, código, callouts e prática | [aulas.json](aulas.json) |
| **Práticas resolvidas** | As 40 práticas, resolvidas e verificadas — 749 asserções | [praticas/](praticas/) |
| **Divergências** | O que o material afirma e o código desmente | [DIVERGENCIAS.md](DIVERGENCIAS.md) |
| **Exercícios originais** | Transcrição do código da aula, feita ao assistir | `Modulo_N/*.php` |
| **Projeto final** | O **Mini CRM** — PHP 8 + MySQL, sem framework | [Modulo_8(modeagem_final)/mini-crm/](Modulo_8%28modeagem_final%29/mini-crm/) |

A lógica aqui é a **do PHPIAN**, não a do PHPAN: a aula é identificada pelo fragmento
`#M-N` da URL da plataforma (`2-3` = Aula 3 do Módulo 2), tem duração em minutos, e os
`.php` são **exercícios do módulo** — não existe um `.php` por aula nem um `.md` por aula.
São 34 das 40 aulas com arquivo ligado; as 6 sem (`1-1`, `1-2`, `1-3`, `5-6`, `6-1`, `6-5`)
são conceituais ou de setup, e nunca geraram código.

Cada exercício abre com duas linhas apontando para o índice:

```php
<?php

// PHPIAN · Módulo 2 · Aula 3 — Loops
// metadados em aulas.json (2-3)

for ($i = 1; $i <= 5; $i++) {
```

## As práticas — [praticas/](praticas/)

Cada aula termina com um bloco **Prática**. Os arquivos em `Modulo_N/` **não** são
essas práticas: são a transcrição do código que a aula mostra, adaptado para rodar.
A prática de verdade — "some 1 a 100 e exiba 5050", "crie `ehEmailValido()`", "teste
o CRUD com 2 usuários" — está em `praticas/`, uma por aula, resolvida e **verificando
o próprio resultado**.

```bash
docker start crm-mysql                 # Módulos 6 a 8 precisam do banco
php praticas/rodar-todas.php           # as 40
php praticas/rodar-todas.php 6         # só o Módulo 6
php praticas/2-3-soma-de-1-a-100.php   # uma
```

**Última execução: 40 práticas · 40 ok · 0 falhas · 749 asserções · 4 passos manuais.**

Os 4 passos manuais são o que não cabe em código — criar a conta no GitHub, contratar
a hospedagem, revisar linha a linha na IDE. Aparecem marcados `[MANUAL]` com o motivo,
em vez de sumirem.

O helper `praticas/_pratica.php` dá `titulo`, `secao`, `checa`, `checaExcecao`, `nota`,
`manual`, `areaTemporaria` e `bancoDaPratica`. Sem Composer: o PHPIAN é curso de
iniciante e as práticas rodam com o PHP puro que o aluno tem na máquina. Cada prática
que mexe em arquivo trabalha numa pasta temporária e limpa no fim.

**O banco.** Os Módulos 6 a 8 usam o container `crm-mysql` (o mesmo do PHPAN) num
banco **separado**, `phpian`, para não encostar no `crm_produto`. Uma vez só:

```bash
docker exec crm-mysql mysql -uroot -praiz-estudo -e \
  "CREATE DATABASE IF NOT EXISTS phpian CHARACTER SET utf8mb4;
   GRANT ALL PRIVILEGES ON phpian.* TO 'crm'@'%'; FLUSH PRIVILEGES;"
```

## O índice — [aulas.json](aulas.json)

Formato descrito em [aulas.schema.json](aulas.schema.json). Por aula: `titulo`, `url`,
`duracao_min`, `resumo`, `secoes` (os h2/h3 com seus parágrafos e listas), `codigo`
(45 blocos, com as entidades HTML já resolvidas — o código como se copia do site),
`callouts` (22, separados em `nota` e `aviso`), `pratica` (as 40 têm), `pratica_arquivo`
(o arquivo em `praticas/` que a resolve) e a corrente `navegacao.anterior` /
`navegacao.proxima`.

```bash
# o que cada aula manda praticar
php -r '$d=json_decode(file_get_contents("aulas.json"),true);
foreach($d["aulas"] as $a) echo $a["id"]." — ".$a["pratica"]."\n";'
```

## As duas ferramentas — [bin/](bin/)

```bash
php bin/importar-aula.php aula.html    # HTML da plataforma -> entrada no aulas.json
php bin/consolidar.php                 # recalcula curso/modulos, valida e checa os .php
```

`importar-aula.php` é **idempotente**: reimportar uma aula sobrescreve só ela, e nunca
apaga o campo `arquivos`, que é preenchido à mão. Aceita vários arquivos ou `stdin`.

`consolidar.php` recalcula os blocos `curso` e `modulos` a partir do que existe, confere
que a corrente `anterior`/`próxima` amarra nos dois sentidos, que todo caminho em
`arquivos` existe em disco, e compila cada exercício.

## Pendências conhecidas

O consolidador reporta duas, herdadas do arquivo original e **não corrigidas** (é registro
do que foi entregue):

- **`Modulo_2/catch.php` está vazio** (0 bytes).
- **`Modulo_2/Erros, debug e try/catch.php`** — o título da aula `2-5` virou nome de
  arquivo e a `/` fez o Linux criar uma pasta. O arquivo também não compila: tem dois
  `<?php`, porque os dois blocos de código da aula foram colados sem remover a segunda
  abertura. Os dois estão registrados em `arquivos` da aula `2-5`.

Faltam também os **títulos dos 8 módulos** — o HTML da aula só traz o número
(`Módulo 2 · 16 min`). Ficam `null` até virem da grade do curso.

E há **10 divergências do material**, achadas ao fazer as práticas e reunidas em
[DIVERGENCIAS.md](DIVERGENCIAS.md) para reportar à plataforma — entre elas o código da
Aula 4-4 contradizendo o próprio callout, o `mensagens.txt` da 4-5 legível pela URL, e
`catch (Exception)` não pegando `DivisionByZeroError` numa aula sobre try/catch.
