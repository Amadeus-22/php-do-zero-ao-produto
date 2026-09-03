# Aula 02 — Access token + refresh token

**Código:** [02-access-refresh-tokens.php](02-access-refresh-tokens.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/02-access-refresh-tokens)

## A ideia

Um token único de vida longa é ruim: se vazar, o atacante usa até alguém perceber. A
separação em dois resolve isso com uma troca clara:

- **Access** (15 min) — vai em toda requisição. Se vazar, a janela é pequena.
- **Refresh** (30 dias) — só serve para pedir um access novo. É guardado com mais
  cuidado e usado raramente.

## Por que token opaco e não JWT

JWT auto-contido **não pode ser revogado** antes de expirar: o servidor não guarda
estado, então não tem onde marcar "este não vale mais". A saída seria uma blocklist —
e aí você já está guardando estado, tendo perdido a vantagem que motivou o JWT.

Token opaco é uma string aleatória sem significado; quem decide se vale é o banco.
Mais simples e revogável de verdade.

## A regra de ouro: hash, nunca o token

```php
'hash' => hash('sha256', $tokenBruto)  // é isto que vai para o banco
return $tokenBruto;                    // única vez que ele existe legível
```

Mesma lógica de senha: se o banco vazar, quem tiver os hashes não consegue usá-los como
credencial. O `.php` confere as duas coisas — que o hash está lá e que o token em claro
**não** está.

> Aqui é `hash('sha256')` e não `password_hash`, porque o token já tem 32 bytes de
> entropia aleatória. `password_hash` é lento de propósito (para resistir a força bruta
> em senhas humanas fracas) e isso não é necessário — nem desejável — num valor que
> ninguém adivinha e que é verificado a cada requisição.

## As três condições da validação

```sql
WHERE token_hash = :hash AND tipo = :tipo
  AND revogado_em IS NULL AND expira_em > NOW()
```

Esquecer `expira_em > NOW()` é a armadilha mais comum: o token expirado continua
funcionando porque a query só checava revogação. O `.php` envelhece um token à força
para provar que a condição está lá.

## Rotação do refresh

`renovar()` revoga o refresh apresentado e emite um par novo. Sem isso, um refresh
vazado uma vez valeria os 30 dias inteiros; com rotação, o estrago fica limitado a uma
janela — e o uso do token antigo vira um sinal detectável de que algo vazou.

## Logout que é logout

`revogarTodosDoUsuario()` marca `revogado_em` no servidor. Apagar o token só no cliente
deixa ele **válido** até expirar: quem tiver uma cópia continua dentro.

## A tentação a evitar

Fazer o access durar um dia "porque dá menos trabalho" anula o padrão inteiro. Se você
sente essa vontade, o problema é o refresh estar difícil de implementar no cliente —
não a duração do token.
