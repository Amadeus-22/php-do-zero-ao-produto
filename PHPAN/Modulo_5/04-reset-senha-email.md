# Aula 04 — Reset de senha por e-mail

**Código:** [04-reset-senha-email.php](04-reset-senha-email.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/04-reset-senha-email)

## A ideia

Três passos, cada um com um jeito errado de fazer:

| Passo | Erro clássico |
|---|---|
| 1. Pedido | responder diferente para conta existente e inexistente |
| 2. Token | previsível, eterno ou reutilizável |
| 3. Troca | deixar as sessões antigas vivas |

## Passo 1 — user enumeration

Se "e-mail não encontrado" aparece para conta inexistente e "enviamos o link" para
existente, o atacante descobre **quais e-mails têm conta** — insumo para phishing e
credential stuffing.

A resposta é sempre a mesma: *"Se esse e-mail estiver cadastrado, você vai receber um
link."* Por isso `solicitar()` retorna `void`: não existe caminho para o controller
responder diferente.

O vazamento pode ser sutil: mensagem, código de status, ou até **tempo de resposta**.
Aqui os dois casos saem sem enviar nada quando não há conta, e o controller responde
igual nos dois.

## Passo 2 — o token

- `random_bytes(32)`, nunca `uniqid()` (previsível pelo relógio) nem `rand()`.
- Guardado como **hash**, igual aos tokens da aula 2.
- **Expiração curta** (1 hora). Um link de reset de 2019 que ainda funciona é porta
  aberta permanente.
- **Uso único**: `usado_em` preenchido na redefinição.
- **Pedido novo invalida os anteriores** — se o usuário pediu 3 vezes, só o último
  link vale. Os outros viram lixo inerte, não lixo válido esquecido.

## Passo 3 — derrubar o resto

Trocar a senha **revoga todos os tokens ativos** do usuário. O motivo é direto: se a
troca foi motivada por suspeita de invasão, deixar a sessão do invasor viva anula o
reset inteiro — ele continua dentro com a senha antiga já não importando.

Isso acontece dentro de uma transação com a troca da senha: ou as duas coisas
acontecem, ou nenhuma.

## O que nunca vai para o log

A URL completa do link de reset. Log tem retenção longa e é lido por mais gente que o
banco de produção — o token no log é uma conta comprometida com prazo de uma hora.
