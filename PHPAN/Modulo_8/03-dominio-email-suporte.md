# Aula 03 — Domínio, e-mail profissional e suporte

**Código:** [03-dominio-email-suporte.php](03-dominio-email-suporte.php) · **Plano:** [plano-dominio-email.md](../crm-produto/docs/plano-dominio-email.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/03-dominio-email-suporte)

## A ideia

Três problemas que viram um só ticket de "configurar e-mail":

1. **Domínio** — identidade do produto (DNS, Módulo 7).
2. **Deliverability** — por que e-mail cai em spam. É **DNS + reputação**, não o
   `mail()` do PHP.
3. **Suporte** — o canal por onde o cliente fala com você.

## Por que e-mail cai em spam

Provedores desconfiam de qualquer servidor que diz "eu sou tal domínio" sem provar:

| Registro | Prova |
|---|---|
| **SPF** | quais servidores podem enviar em nome do domínio |
| **DKIM** | assinatura criptográfica de que o conteúdo não mudou |
| **DMARC** | política quando SPF/DKIM falham + relatórios |

`mail()` direto de um VPS, sem provedor transacional: IP compartilhado, sem reputação,
sem DKIM gerenciado. Quase sempre spam.

`DMARC` com `p=reject` no primeiro dia pode bloquear e-mail legítimo enquanto o DNS
estabiliza — comece em `none` ou `quarantine`.

## `no-reply@` com `Reply-To` para suporte

O remetente transacional é `no-reply@`, mas o **`Reply-To` aponta para `suporte@`**. O
cliente que responde "não consigo logar" precisa chegar em algum lugar. Sem isso, a
resposta dele cai no vazio — e ele conclui que não há suporte.

## Trocar o provedor é uma linha

Todo o sistema depende da interface `RemetenteDeEmail`. Hoje a implementação grava em
`var/emails.log`; quando o provedor transacional entrar, muda o `Container` e **mais
nada**. O `.php` verifica que nenhuma outra classe conhece a implementação concreta.

É o retorno do Módulo 2 aparecendo de novo.

## Suporte mínimo viável

Não precisa de Zendesk. Precisa de: alias que alguém **de fato lê**, SLA escrito, e um
lugar único onde pedido vira tarefa rastreável.

Canal que existe no papel e ninguém checa é pior que não anunciar suporte.

## Estado deste projeto

Sem domínio próprio. O plano está em `docs/plano-dominio-email.md` com os registros DNS
exatos e o checklist de validação. A rubrica aceita isso como **pendência justificada**,
não como item feito.
