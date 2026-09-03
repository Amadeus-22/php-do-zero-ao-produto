# Aula 02 — Checkout conceitual, gateway e webhooks

**Código:** [02-checkout-webhooks.php](02-checkout-webhooks.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/02-checkout-webhooks)

## A ideia

**Você nunca processa número de cartão.** Isso é PCI-DSS: responsabilidade legal e
técnica pesada que todo gateway sério resolve via checkout hospedado ou tokenização no
client-side. Seu papel é orquestrar o fluxo.

```
backend cria a intenção → gateway devolve URL → cliente paga NO GATEWAY
   → redirect de volta (UX)  +  WEBHOOK (verdade)  → backend atualiza a assinatura
```

## O redirect não libera acesso

É o passo que mais gente erra. O cliente pode fechar a aba, a rede pode cair, ele pode
nunca voltar. O redirect é **UX**; a fonte de verdade é o webhook, que chega de forma
assíncrona e independente.

## Assinatura HMAC: a URL é pública

Endpoint de webhook é uma URL aberta na internet. Sem verificação, qualquer um manda
`{"type":"payment.succeeded"}` e libera acesso de graça.

```php
hash_equals(hash_hmac('sha256', $payload, $this->chaveSecreta), $recebida)
```

`hash_equals` e não `===`: comparação em tempo constante, não vaza por timing.

**O corpo tem que ser o CRU.** A assinatura é dos bytes exatos que chegaram —
decodificar o JSON e reserializar muda ordem de chaves e espaços, e o HMAC nunca mais
bate. Por isso o `Request` guarda `corpoCru`.

## Idempotência: gateways reenviam

Se seu servidor demora a responder `200`, ou a rede falha no meio, **o mesmo evento
chega de novo** — às vezes várias vezes. Sem controle, o mesmo pagamento é creditado
duas ou três vezes.

A defesa é o `UNIQUE` em `eventos_webhook.evento_externo_id`: **quem garante é o banco**,
não um `if` na aplicação. E o `INSERT` vai na mesma transação da mudança de assinatura —
senão dá para ter evento marcado como processado sem a assinatura ter mudado.

## Responder 200 para o que não interessa

Gateway envia muitos eventos. Qualquer coisa fora de `2xx` é interpretada como falha de
entrega, e ele reenvia agressivamente. Por isso:

- evento de tipo desconhecido → **200**, ignorado
- evento repetido → **200**, `ja_processado`
- falha **nossa** → **500**, que é quando queremos o reenvio

## Nunca guarde dado de cartão

Nem número, nem CVV, nem "só os últimos quatro dígitos por conveniência". Token do
gateway resolve todos os casos legítimos.
