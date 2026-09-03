# Aula 04 — Notificações e lembretes (agenda do CRM)

**Código:** [04-notificacoes-lembretes.php](04-notificacoes-lembretes.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/04-notificacoes-lembretes)

## A ideia

Todo CRM tem agenda: o vendedor registra "ligar pro fulano dia 15" e o **sistema**
lembra — não fica esperando ele lembrar sozinho. São duas peças: o dado (lembrete) e o
gatilho (cron verificando o que venceu).

## Fuso: o detalhe que mais gera bug

**Grava em UTC, converte só na exibição.** O `.php` mostra: o vendedor digita 09:00 em
São Paulo, o banco guarda `12:00:00` UTC, e a listagem devolve `09:00` de volta.

Salvar hora local funciona na sua máquina e quebra sutilmente quando servidor e usuário
estão em fusos diferentes — ou no horário de verão, quando uma hora acontece duas vezes.

## Cron idempotente

Dois riscos, mesma defesa:

```sql
SELECT id FROM lembretes
 WHERE status = 'pendente' AND vence_em <= UTC_TIMESTAMP()
 FOR UPDATE SKIP LOCKED
```

- **`SKIP LOCKED`** — se dois crons rodarem juntos (erro de config, dois servidores), o
  mesmo lembrete não vira duas notificações.
- **Marcar `notificado` na MESMA transação** que selecionou. Marcar depois abre uma
  janela onde outro processo pega o mesmo registro.

## `<=` e não `=`

Se a condição fosse `vence_em = UTC_TIMESTAMP()`, um cron que ficou 10 minutos fora do
ar perderia **para sempre** os lembretes daquele intervalo. Com `<=`, os atrasados são
pegos na execução seguinte.

## O cron despacha; o worker envia

O cron só cria o job e termina. Quem manda o e-mail é o worker da aula 2 — e assim o
envio ganha retry e backoff de graça, sem o cron ficar preso esperando SMTP.

## Uma armadilha de sintaxe que me pegou

Escrever `*/5 * * * *` (a linha do cron) dentro de um bloco `/** */` **fecha o
comentário** no `*/` do cron, e o arquivo deixa de compilar. Em
`bin/verificar-lembretes.php` o cron está em comentário de linha (`//`) por isso.

## Notificação passiva não basta

Se o lembrete só aparece numa lista que o vendedor não abre, ele não foi lembrado. Por
isso existe canal ativo (e-mail) além do in-app.
