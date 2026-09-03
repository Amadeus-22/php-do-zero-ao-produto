# Checklist de hardening

Não é técnica nova — é **verificar** que o que já deveria estar feito está de fato no
lugar, e fechar as lacunas.

| Item | Estado | Onde |
|---|---|---|
| SQL injection: prepared statements em toda query | ✅ | repositórios PDO; `EMULATE_PREPARES=false` |
| XSS: escape em toda saída de view | ✅ | `View::e()` com `ENT_QUOTES` |
| CSRF: token em todo formulário de escrita | ✅ | `CsrfMiddleware` + `Csrf::token()` |
| Rate limit em rotas sensíveis | ✅ | login web, `/auth/login` e **`/esqueci-senha`**; falta em `/webhooks` |
| Cabeçalhos de segurança | ✅ | `SecurityHeaders` em todas as rotas |
| Dependências sem vulnerabilidade conhecida | ✅ | `composer audit` no `composer quality` |
| Segredo fora do código | ✅ | `.env`, verificado na aula 1 do Módulo 7 |
| Senha com `password_hash` | ✅ | `Usuario::novo()` |
| Token guardado como hash | ✅ | `TokenService` |
| Upload validado por conteúdo | ✅ | `finfo` + destino fora de `public/` |
| Auditoria append-only | ✅ | verificado por teste |
| OPcache ativo em produção | ⏳ | depende de servidor |
| Índices revisados nas queries mais usadas | ✅ | `idx_clientes_ativo`, `idx_jobs_status_disponivel` |

## Cabeçalhos e o que cada um evita

- `X-Content-Type-Options: nosniff` — o browser não "adivinha" o tipo do conteúdo.
- `X-Frame-Options: DENY` — clickjacking: o painel não carrega em iframe de terceiro.
- `Referrer-Policy: strict-origin-when-cross-origin` — não vaza a URL completa (com ids)
  para sites externos.
- `Content-Security-Policy` — começa em `'self'` e abre exceção pontual. CSP permissiva
  (`default-src *`) não protege nada.
- `Strict-Transport-Security` — **só em produção**. HSTS é "pegajoso" no navegador:
  ativado antes do HTTPS estar 100% estável, tranca quem tiver problema de certificado.

## `composer audit`

Compara as dependências instaladas contra uma base de vulnerabilidades conhecidas. Está
dentro do `composer quality`, então roda antes de qualquer deploy — vulnerabilidade em
dependência é o risco mais fácil de evitar e o mais comum de ignorar.

## Pendências honestas

- **Rate limit no webhook** — o endpoint é público; mesmo com HMAC, um limite evita
  sobrecarga.
- **OPcache** — configuração de servidor, e não há servidor ainda.
- **`UNIQUE` + soft delete** — e-mail de cliente excluído ainda ocupa a constraint
  (Módulo 6, aula 6).
