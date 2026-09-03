# Aula 06 — Observabilidade mínima

**Código:** [06-observabilidade-minima.php](06-observabilidade-minima.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/06-observabilidade-minima)

## A ideia

Três perguntas, nesta ordem de prioridade:

1. **Está no ar?** — monitor externo
2. **Fico sabendo quando quebra, e rápido?** — log + alerta
3. **Consigo entender depois?** — log com contexto

Não é preciso métrica por percentil nem tracing distribuído num monólito com poucos
usuários. Isso é over-engineering nesta fase — fica para o PHPPRO, se a escala pedir.

## O monitor tem que ser externo

Se o próprio servidor monitora a si mesmo e cai, ninguém fica sabendo. UptimeRobot,
Better Stack ou Healthchecks batendo em `/health` a cada 5 minutos, com alerta por
e-mail ou webhook.

**Ainda não configurado** aqui: não há domínio público. Declarado, não simulado.

## Alerta de taxa de erro sem ferramenta paga

`scripts/checar-taxa-erro.sh` varre o log da última janela, conta `error`/`critical` e
sai com código 1 se passar do limite — o cron detecta e avisa.

O `.php` roda o script duas vezes: com log tranquilo (sem alerta) e com 12 erros
(alerta + exit 1).

Rústico e funcional, e muito mais barato — em dinheiro e complexidade — do que integrar
um APM num sistema com um punhado de usuários.

## É o formato JSON Lines que torna isso possível

```bash
grep '"nivel":"error"' var/logs/app.jsonl
```

Cada linha tem `timestamp` e `nivel` em campo próprio, então filtrar por nível e cortar
por janela de tempo é `grep` + `awk`. Com log de texto livre, nada disso funciona.

## Rotação não é detalhe

Sem `logrotate`, um dia o disco enche — e aí a aplicação inteira cai, **inclusive a
escrita de novos logs**. O incidente vira "servidor travado por log", pior que o bug
original. 14 dias, comprimido, com `copytruncate`.

## O que logar e o que não

| Logar | Não logar |
|---|---|
| falha de autenticação (sem a senha) | senha, token, cartão |
| exceção não tratada | payload de webhook com dado sensível |
| falha de envio de e-mail/fila | CPF em texto puro sem necessidade |
| requisição lenta (acima do limiar) | corpo inteiro de toda requisição |

O `Logger` remove os campos sensíveis sozinho, inclusive aninhados — porque o caso comum
é logar um payload inteiro "só para debugar" e esquecer de tirar.

Também: **logar dentro de loop sem limite** gera arquivo gigante e lento de escrever.
Logue o resumo fora do loop.

## Alerta que ninguém olha é o mesmo que nenhum alerta

Configurar e-mail para uma caixa que não é checada não conta. No script, o ponto de
envio real está marcado — o canal precisa ser um que você de fato olha.
