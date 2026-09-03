# Aula 01 — Upload seguro de anexos

**Código:** [01-upload-seguro.php](01-upload-seguro.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/01-upload-seguro)

## A ideia

Upload é das superfícies mais exploradas de aplicação web porque o cliente controla
três coisas em que se tende a confiar: **nome do arquivo, extensão e `Content-Type`**.
Nenhuma das três é verificável — todas vêm do navegador.

## Os três riscos

| Risco | Como acontece |
|---|---|
| **Arquivo executável** | subir `.php` numa pasta servida publicamente → o atacante roda código no servidor |
| **Path traversal** | nome tipo `../../public/shell.php` usado para montar o caminho |
| **MIME spoofing** | `Content-Type` é escolhido pelo navegador a partir da extensão, não do conteúdo |

## A defesa, em camadas

1. **`finfo` lê o conteúdo real** (magic number, os primeiros bytes). Um `.php`
   renomeado para `.jpg` continua sendo PHP por dentro, e é isso que o `finfo` vê.
   A lista de permitidos é `mime real => extensão`, e é o **servidor** quem atribui a
   extensão — nunca o cliente.
2. **Nome novo** (`bin2hex(random_bytes(16))`). Mata path traversal e resolve colisão:
   dois uploads chamados `foto.png` não se sobrescrevem.
3. **`basename()` no nome original** antes de guardar como metadado.
4. **Fora do document root** (`storage/anexos`). É a camada que salva você se todas as
   outras falharem: um `.php` ali não é alcançável por URL.
5. **Download por rota** que checa permissão — nunca link estático para dado sensível.

## O erro que passa despercebido

```php
if ($arquivo['error'] !== UPLOAD_ERR_OK) { ... }
```

Um upload interrompido (`UPLOAD_ERR_PARTIAL`) **ainda cria entrada em `$_FILES`**. Sem
checar o código de erro, o sistema processa um arquivo incompleto como se estivesse bom.

## Limite de tamanho

Sem checagem, um upload de 2 GB derruba disco ou memória. Note que `$_FILES['size']` vem
do cliente — a defesa real de tamanho também depende de `upload_max_filesize` e
`post_max_size` no PHP, que barram antes de o script rodar.
