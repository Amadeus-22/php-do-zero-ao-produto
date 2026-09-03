# Divergências do material — PHPIAN

Achados ao **fazer as 40 práticas** (`praticas/`) e rodar o código de cada aula.
Cada item traz a aula, o que o material diz, o que acontece de verdade e como
reproduzir. Ordenado por gravidade.

Ambiente: PHP 8.4.25 · MySQL 8.4 · Linux. Data: 01/09/2026.

---

## 1. Aula 4-4 — o código contradiz o próprio callout

**O material diz**, no callout: *"Valide MIME real, limite tamanho e salve **fora da
raiz pública** quando possível."*

**O código da mesma aula faz o contrário:**

```php
move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/uploads/' . $nome);
```

`__DIR__` é a pasta do script que recebe o upload. Na estrutura que a Aula 5-2
ensina (`public/` como DocumentRoot), esse script fica em `public/` — então
`uploads/` nasce **dentro** da raiz pública, exatamente o que o callout pede para
evitar.

**Sugestão:** `__DIR__ . '/../storage/uploads/'`, com um script que lê e devolve o
arquivo. Assim o código e o callout dizem a mesma coisa.

**Prática que cobre:** `praticas/4-4-upload-png-jpeg.php`

---

## 2. Aula 4-5 — `mensagens.txt` fica legível pela URL

**O material manda** gravar em `__DIR__ . '/mensagens.txt'`, no mesmo diretório do
`contato.php`. Como o `contato.php` é servido por HTTP, o arquivo com **nome,
e-mail e mensagem de todo mundo que escreveu** também é.

**Reproduzido:**

```
$ curl -i http://localhost/mensagens.txt
HTTP/1.1 200 OK

2026-01-01 | Ana | ana@x.com | segredo do usuario
```

A Aula 5-4 põe `mensagens.txt` no `.gitignore`, o que resolve o vazamento **para o
Git** — mas não para a web. São dois problemas diferentes, e só um é tratado.

**Sugestão:** gravar fora da raiz pública, ou bloquear por `.htaccess`. E dizer na
aula que `.gitignore` não protege de HTTP.

**Prática que cobre:** `praticas/4-5-projeto-relampago-contato.php`

---

## 3. Aula 2-5 — `catch (Exception)` não pega divisão por zero

**O material** ensina `try/catch` usando uma exceção lançada à mão
(`InvalidArgumentException`) e não menciona o que o PHP faz sozinho.

**O que acontece:** `10 / 0` no PHP 8 lança `DivisionByZeroError`, que estende
`Error`, **não** `Exception`. Quem escrever o `catch` "natural" não pega nada:

```php
try {
    $x = 10 / 0;
} catch (Exception $e) {   // não entra aqui
    echo 'peguei';
}
// Fatal error: Uncaught DivisionByZeroError
```

Precisa ser `catch (DivisionByZeroError)` ou `catch (Throwable)`. Numa aula
chamada *"Erros, debug e try/catch"*, a distinção `Error` × `Exception` é o
conteúdo central e está ausente.

**Prática que cobre:** `praticas/2-5-erro-e-try-catch.php`

---

## 4. Aula 6-5 — a tabela de junção não tem chave estrangeira

**O material define:**

```sql
CREATE TABLE contato_tag (
  contato_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (contato_id, tag_id)
);
```

Sem `FOREIGN KEY`, o banco aceita associar um contato **que não existe**:

```sql
INSERT INTO contato_tag VALUES (999999, 1);   -- passa
```

E apagar um contato deixa as associações órfãs para sempre. Numa aula sobre
*relações*, a constraint que define a relação é justamente a que falta.

**Sugestão:**

```sql
FOREIGN KEY (contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
```

**Prática que cobre:** `praticas/6-5-tags-com-join.php`

---

## 5. Aula 3-2 — o bloco de código não roda como está

`$usuario` é usado sem nunca ser definido — vem da Aula 3-1 e não foi trazido:

```php
array_key_exists('nome', $usuario);
```

```
Warning: Undefined variable $usuario on line 5
```

Quem copiar o bloco inteiro, como a aula sugere, leva um warning na cara.

---

## 6. Aula 3-3 — o exemplo de slug contraria o callout da própria aula

**O callout diz:** *"Em português, prefira funções `mb_*` para não quebrar acentos."*

**O exemplo, logo acima, usa `strtolower`:**

```php
$slug = str_replace(' ', '-', strtolower(trim($texto)));
```

Com `"Ação Rápida"` o resultado é `ação-rápida` — acento cru dentro de uma URL.
Um slug precisa de transliteração (`iconv('UTF-8', 'ASCII//TRANSLIT', ...)`), não
só de `mb_*`.

**Prática que cobre:** `praticas/3-3-iniciais-do-nome.php`

---

## 7. Aula 6-2 — a justificativa comum de `EMULATE_PREPARES` está desatualizada

Não é erro do material (ele não explica o porquê), mas evita repetir conselho
velho: até o PHP 8.0, emulação devolvia todo valor como **string**. **Desde o
8.1 os dois modos devolvem tipo nativo**, e só `PDO::ATTR_STRINGIFY_FETCHES`
força string.

Medido no PHP 8.4.25:

| modo | tipo de uma coluna `INT` |
|---|---|
| `EMULATE_PREPARES => true` | `integer` |
| `EMULATE_PREPARES => false` | `integer` |
| `STRINGIFY_FETCHES => true` | `string` |

O ganho real de desligar a emulação continua valendo, mas é outro: o SQL vai ao
servidor **separado** do dado.

**Prática que cobre:** `praticas/6-2-db-php-pdo.php`

---

## 8. `mini-crm/config/app.example.php` — inseguro por omissão

O modelo que o aluno copia para produção vem com:

```php
'debug' => true,                 // stack trace visível para o visitante
'allow_registration' => true,    // qualquer um cria conta
```

Os comentários avisam ("true só em localhost", "Desligue depois de criar os
usuários"), mas o padrão de um **exemplo** deveria ser o valor seguro — quem
esquece de trocar publica com debug ligado e cadastro aberto. A Aula 8-5 é
enfática em `display_errors = Off` em produção; o modelo do projeto contradiz.

**Prática que cobre:** `praticas/8-5-readme-e-publicacao.php`

---

## 9. Aula 1-2 — só cobre Windows

A seção é *"Passo 1 — Instalar o ambiente (Windows)"* e as quatro seções seguintes
assumem Laragon/XAMPP. Não há caminho para Linux ou macOS, e o curso roda em
qualquer um dos três.

---

## 10. Aula 4-5 — concordância no resumo

> "Antes do CRM final, **um** vitória rápida"

Deveria ser *uma vitória rápida*.

---

## Como reproduzir tudo

```bash
docker start crm-mysql
php praticas/rodar-todas.php
```

As práticas que demonstram cada item estão citadas acima. Todas passam — elas
verificam o comportamento **real**, incluindo os pontos em que ele difere do que
a aula afirma.
