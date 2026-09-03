# Aula 03 — Logs estruturados

**Código:** [03-logs-estruturados.php](03-logs-estruturados.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/03-logs-estruturados)

## A ideia

Em produção não existe `var_dump` na tela: o que aconteceu só existe se ficou
registrado. E texto livre (`"Erro ao salvar cliente"`) funciona até você precisar
filtrar, buscar ou agregar milhares de linhas.

**Log estruturado** = uma linha, um objeto JSON (formato *JSON Lines*, `.jsonl`). Cada
campo vira pesquisável com `grep`, `jq` ou qualquer plataforma de log — sem ferramenta
especial:

```bash
grep '"nivel":"error"' var/logs/app.jsonl | tail -20
```

> Aqui o JSON é **formato de saída**, não armazenamento: nada relê esse arquivo como se
> fosse banco. É a distinção que o `docs/separacao-de-arquivos.md` fixa.

## Os cinco níveis (PSR-3, resumidos)

| Nível | Quando |
|---|---|
| `debug` | detalhe técnico útil só em desenvolvimento |
| `info` | evento normal (login feito, job processado) |
| `warning` | estranho, mas seguiu (retry, rate limit acionado) |
| `error` | uma operação falhou |
| `critical` | o sistema está comprometido (banco inacessível) |

**Se tudo é `error`, nada é `error`.** O alerta perde sentido e ninguém mais olha.

## Contexto é o que torna o log útil

`"erro ao processar"` sozinho, no meio de 500 linhas parecidas, obriga a adivinhar. Por
isso toda linha carrega `request_id` (correlaciona a requisição inteira) e `usuario_id`.

## O que nunca vai para o log

Senha, token, `Authorization`, cartão. O filtro do `Logger` remove esses campos —
inclusive **aninhados**, porque o caso comum é logar um payload inteiro "só para
debugar" e esquecer de tirar.

Log tem retenção longa e é lido por mais gente que o banco de produção. Trate como um
asset que também pode vazar, não como gaveta onde tudo cabe.

## Log × auditoria (Módulo 5)

Log é para dev, rotaciona e some em semanas. Auditoria é rastro de negócio, append-only,
guardado por anos. Confundir os dois leva a apagar prova junto com ruído.
