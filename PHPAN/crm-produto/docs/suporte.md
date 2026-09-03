# Suporte

Canal que existe no papel e ninguém checa é pior que não anunciar suporte.

## Canal

- **E-mail:** `suporte@crm.exemplo.com` (alias → caixa monitorada)
- **Reply-To de todo e-mail transacional** aponta para cá. O remetente é
  `no-reply@`, mas quem responde "não consigo logar" precisa chegar em algum lugar.

## SLA

| Tipo | Primeira resposta |
|---|---|
| Bug que impede uso | 4h úteis |
| Bug sem workaround | 24h úteis |
| Dúvida | 24h úteis |
| Feature request | 72h úteis (resposta, não entrega) |

## Triagem

1. E-mail chega em `suporte@`.
2. Classificar: **bug**, **dúvida**, **cobrança**, **feature request**.
3. Bug → issue no Git com passos de reprodução, `request_id` do log se houver.
4. Dúvida → responder; se repetir, vira entrada na FAQ.
5. Cobrança → conferir `assinaturas` e `eventos_webhook` antes de responder.

## Estado deste projeto

Não há domínio nem caixa configurada. O `.env` já tem `MAIL_FROM` e `MAIL_REPLY_TO`, e o
`RemetenteDeEmailEmLog` grava em `var/emails.log` — o envio real (provedor
transacional) entra quando o domínio existir.
