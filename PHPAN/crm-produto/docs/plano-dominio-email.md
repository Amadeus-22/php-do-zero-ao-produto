# Plano: domínio, e-mail profissional e deliverability

Este projeto **ainda não tem domínio próprio**. Em vez de fingir que a entrega foi
feita, aqui está o plano executável.

## Por que e-mail cai em spam

Provedores desconfiam de qualquer servidor que diz "eu sou tal domínio" sem provar via
DNS. Três registros resolvem a maior parte:

| Registro | Função |
|---|---|
| **SPF** | quais servidores podem enviar em nome do domínio |
| **DKIM** | assinatura criptográfica provando que o conteúdo não foi alterado |
| **DMARC** | política quando SPF/DKIM falham + endereço para relatórios |

`mail()` do PHP direto de um VPS, sem provedor transacional, quase sempre cai em spam:
IP compartilhado, sem reputação, sem DKIM gerenciado.

## Registros a criar

```dns
; SPF — autoriza o provedor de envio
crm.exemplo.com.   TXT   "v=spf1 include:_spf.provedor-envio.com ~all"

; DKIM — chave pública fornecida pelo provedor
resend._domainkey.crm.exemplo.com.  TXT  "v=DKIM1; k=rsa; p=MIGfMA0GCSq..."

; DMARC — começar em none/quarantine, NUNCA reject no primeiro dia
_dmarc.crm.exemplo.com.  TXT  "v=DMARC1; p=quarantine; rua=mailto:dmarc@crm.exemplo.com"
```

`p=reject` logo de início pode bloquear e-mail legítimo enquanto o DNS estabiliza.

## Passos

1. Registrar o domínio e apontar o DNS para o servidor (Módulo 7, aula 4).
2. Escolher provedor transacional (Resend, Mailgun, SES, Postmark) e adicionar o
   domínio de envio.
3. Publicar SPF, DKIM e DMARC.
4. Trocar `RemetenteDeEmailEmLog` por uma implementação SMTP/API — **uma linha no
   `Container`**, porque tudo depende da interface `RemetenteDeEmail`.
5. Disparar e-mail de teste e validar em ferramenta externa (mail-tester), registrando a
   pontuação.
6. Testar a rota completa: receber na inbox → responder → confirmar que chega em
   `suporte@`.

## Checklist de validação

- [ ] SPF passa em ferramenta externa
- [ ] DKIM com assinatura válida
- [ ] DMARC publicado
- [ ] E-mail de teste na inbox do Gmail **e** do Outlook (não em spam)
- [ ] `Reply-To` testado com resposta manual
- [ ] Domínio do `From` bate com o domínio autenticado no provedor
