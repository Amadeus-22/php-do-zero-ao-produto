# Aula 01 — Sessão vs token: limites de cada um

**Código:** [01-limites-sessao-vs-token.php](01-limites-sessao-vs-token.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/01-limites-sessao-vs-token)

## A ideia

O CRM tem **dois mecanismos de autenticação ao mesmo tempo**, e isso não é
inconsistência — é adequação ao contexto.

| | Sessão | Token |
|---|---|---|
| Onde | painel web | API |
| Estado | no servidor (`$_SESSION`) | no banco (hash do token) |
| Quem envia | o navegador, sozinho (cookie) | o cliente, no header `Authorization` |
| Serve para | navegador conversando com o próprio servidor | app, script, outro backend |
| Risco principal | CSRF, session fixation | XSS, vazamento em log/URL |
| Logout | trivial (destrói no servidor) | precisa revogar (aula 2) |

**Regra: uma rota, um mecanismo.** Rota que aceita cookie *ou* token sem cuidado extra
abre brecha nos dois sentidos. O `.php` verifica: sessão de painel ativa **não**
autentica `/api/v1/clientes` — vem 401.

## As três flags do cookie

```php
ini_set('session.cookie_httponly', '1');   // JS não lê o cookie
ini_set('session.cookie_samesite', 'Lax'); // mitiga CSRF cross-site
ini_set('session.use_strict_mode', '1');   // ignora ID inventado pelo cliente
```

`HttpOnly` é o que impede um XSS de virar roubo de sessão: mesmo com script injetado
na página, `document.cookie` não enxerga o cookie.

## Session fixation: o motivo do `session_regenerate_id`

O ataque: o atacante faz a vítima usar um ID de sessão que **ele** conhece (link com
ID na URL, cookie plantado). A vítima faz login normalmente. Como o ID não mudou, o
atacante agora está dentro da conta — sem nunca saber a senha.

`session_regenerate_id(true)` no login troca o ID naquele instante. O ID fixado morre.
O `.php` imprime o ID antes e depois para mostrar a troca acontecendo.

## Logout: destruir dos dois lados

`session_destroy()` limpa o servidor. Só isso deixa o navegador continuando a mandar o
cookie — em alguns cenários ele é reaproveitável até expirar. O logout completo:

1. `$_SESSION = []`
2. expirar o cookie com `setcookie(..., time() - 3600, ...)`
3. `session_destroy()`

## Sessão eterna

Sem expiração, uma sessão roubada uma vez vale para sempre. `Sessao::TEMPO_MAXIMO` é 2h;
a checagem acontece na leitura, e sessão vencida é derrubada na hora.

## O que não resolve

- **`localStorage` "é mais seguro que cookie"** — não é. Qualquer script na página lê
  `localStorage`; um cookie `HttpOnly`, não.
- **"HTTPS resolve"** — HTTPS protege o **transporte**. Não protege contra XSS, CSRF
  nem token mal guardado no cliente.
