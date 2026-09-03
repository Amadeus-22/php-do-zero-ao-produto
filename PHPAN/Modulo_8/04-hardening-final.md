# Aula 04 — Hardening final

**Código:** [04-hardening-final.php](04-hardening-final.php) · **Checklist:** [hardening.md](../crm-produto/docs/hardening.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/04-hardening-final)

## A ideia

Não é aprender técnica nova — é **verificar** que o que já deveria estar feito está de
fato no lugar. Código muda; um formulário novo pode ter entrado sem o token CSRF.

Por isso o `.php` não confere de memória: ele lê o código-fonte e prova cada item.

## Cabeçalhos de segurança

| Cabeçalho | O que evita |
|---|---|
| `X-Content-Type-Options: nosniff` | o browser "adivinhar" o tipo do conteúdo |
| `X-Frame-Options: DENY` | clickjacking (painel dentro de iframe alheio) |
| `Referrer-Policy` | vazar a URL completa, com ids, para sites externos |
| `Content-Security-Policy` | execução de script de origem não autorizada |
| `Strict-Transport-Security` | downgrade para HTTP |

Dois cuidados:

- **CSP começa restritiva** (`'self'`) e abre exceção pontual. `default-src *` não
  protege nada — é CSP no papel.
- **HSTS só em produção.** É "pegajoso" no navegador do usuário: ativado antes do HTTPS
  estar 100% estável, quem tiver problema de certificado fica travado sem conseguir cair
  para HTTP. Por isso o middleware só o envia quando `APP_ENV=production`.

## `composer audit` no portão

Compara as dependências instaladas contra uma base de vulnerabilidades conhecidas.
Entrou no `composer quality`, então roda antes de todo deploy.

> O script teve que se chamar `security`: `audit` colidiria com o comando nativo do
> Composer, que avisa e ignora o script.

Vulnerabilidade em dependência é o risco mais fácil de evitar e o mais comum de ignorar.

## Como detectar SQL injection sem falso positivo

A primeira versão da verificação usava regex sobre o arquivo e acusou **13 arquivos
inocentes** — a variável estava fora da string, no `execute()` da linha de baixo. Regex
não distingue "dentro da string" de "na linha seguinte".

A versão que ficou usa `token_get_all()` e procura interpolação **real**: string de
aspas duplas contendo palavra SQL e variável dentro.

Sobraram dois casos, e os dois são deliberados:

| Arquivo | Interpolação | Defesa |
|---|---|---|
| `PlanLimiter` | nome de coluna | whitelist com exceção |
| `RepositorioDeClientesPdo` | `LIMIT`/`OFFSET` | `sprintf('%d')` |

Nenhum dos dois pode ser parâmetro preparado — é limitação do protocolo, não descuido.
A verificação exige a defesa específica de cada um em vez de fingir que não existem.

## O mesmo cuidado nas views

A checagem de XSS lista as saídas `<?= ... ?>` e aceita como seguras: escapadas com
`View::e()`, com cast `(int)`, literais puras, ou ternário cujos dois ramos são
literais. O resto falha.

## Performance básica

`OPcache` ativo (com `validate_timestamps=0` e reload no deploy) e índices nas queries
mais usadas. `EXPLAIN` mostrando `type: ALL` em tabela que vai crescer significa índice
faltando.

## As pendências ficam escritas

Rate limit no webhook, OPcache (depende de servidor) e `UNIQUE` + soft delete estão em
`docs/hardening.md` como pendências — não marcadas como feitas.
