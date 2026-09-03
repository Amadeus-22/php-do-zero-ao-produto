# Aula 05 — Rate limit em rotas sensíveis

**Código:** [05-rate-limit-rotas.php](05-rate-limit-rotas.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/05-rate-limit-rotas)

## A ideia

O objetivo **não** é impedir toda tentativa — é tornar força bruta lenta demais para
valer a pena. Um script que testaria milhares de senhas por minuto passa a conseguir 5
a cada 15 minutos.

## Janela fixa, e por que basta

Conta quantas tentativas houve na janela; passou do limite, bloqueia até a janela virar.

Existe coisa mais sofisticada (sliding window, token bucket). Para uma rota de login,
é over-engineering: janela fixa resolve o problema real com uma query de `COUNT`.

## Por que uma tabela, sem Redis

O volume de tentativas de login é baixo perto do tráfego geral — cabe no banco que já
existe. Se Redis entrar depois, a mesma interface (`RateLimiter::atingiu()`) troca de
implementação sem tocar em quem chama. É a lição do Módulo 2 valendo aqui.

## A chave é `identificador + IP`

| Chave | Problema |
|---|---|
| só IP | pune o escritório inteiro atrás do mesmo NAT por causa de uma pessoa; e não impede ataque distribuído |
| só e-mail | o atacante roda em paralelo contra e-mails diferentes, sem limite |
| **e-mail + IP** | equilíbrio prático |

O `.php` prova os dois lados: depois de esgotar o limite, **outro** e-mail no mesmo IP
continua livre, e o **mesmo** e-mail de outro IP também.

## `Retry-After` não é detalhe

Sem esse cabeçalho, o cliente (app, integração) não sabe quanto esperar e tenta de novo
imediatamente — piorando exatamente o problema que o limite deveria conter.

## Limpeza é obrigatória

Sem `limparAntigos()` rodando em cron, a tabela de tentativas cresce para sempre. É o
tipo de coisa que ninguém nota até o disco encher.

## Onde mais aplicar

`POST /auth/login` (força bruta) · `POST /esqueci-senha` (spam e enumeração) ·
`POST /webhooks/*` (endpoint público) · exportação (endpoint caro que varre a base).

**Rate limit não substitui autorização.** São camadas diferentes: uma limita
frequência, a outra limita permissão. As duas precisam existir.
