# Aula 04 — Deploy (VPS ou hospedagem avançada) + HTTPS

**Código:** [04-deploy-https.php](04-deploy-https.php) · **Arquivos:** [deploy/](../crm-produto/deploy/) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/04-deploy-https)

## As três decisões, iguais nas duas rotas

**1. Document root aponta para `public/`, nunca para a raiz do repositório.** Com a raiz
exposta, `https://seudominio.com/.env` entrega senha de banco e `/composer.json` entrega
a stack inteira. O teste de aceitação é literal: acesse os dois e confirme 403/404.

**2. HTTPS não é opcional.** Sem TLS, cookie de sessão e token de API trafegam em texto
puro. Let's Encrypt é grátis — não existe desculpa de custo.

**3. Deploy é processo repetível**, não `scp` manual. Cada deploy "na mão" é uma chance
de esquecer um passo.

## Releases + symlink

```
releases/20260901-120000/
releases/20260901-143000/
current -> releases/20260901-143000/
shared/{.env,storage,var}
```

O padrão existe por causa do **rollback**: trocar o symlink é atômico e leva menos de um
segundo. Por isso as 5 últimas releases ficam em disco em vez de serem apagadas.

`shared/` guarda o que não pode ser recriado a cada deploy: o `.env` (não vai no Git) e
`storage/` (tem os anexos dos usuários).

## `--no-dev` no Composer

PHPUnit, PHPStan e CS-Fixer não têm o que fazer no servidor de produção. Menos
dependência instalada = menos superfície de ataque e deploy mais rápido.
`--optimize-autoloader` gera o mapa de classes completo, evitando busca em disco.

## O esquecimento clássico: recarregar o FPM

Com `opcache.validate_timestamps=0` (o certo em produção), o OPcache **não percebe**
mudança de arquivo. Sem `systemctl reload php8.3-fpm` no fim do deploy, o código antigo
continua servindo por horas — e você fica olhando para um bug já corrigido.

## Smoke test dentro do deploy

O script bate em `/health` no fim. Se não responder 200, o deploy avisa em vez de deixar
você descobrir pelo cliente.

## O bloco que bloqueia dotfiles

```nginx
location ~ /\.(?!well-known) { deny all; }
```

Segunda barreira, caso o document root esteja errado. A exceção `.well-known` é
necessária: sem ela o certbot não consegue renovar o certificado.

## Worker e cron também são deploy

`deploy/crm-worker.service` (systemd) mantém a fila rodando; `deploy/crontab.txt` tem
lembretes, backup e limpeza; `deploy/logrotate.conf` impede o disco de encher. Sem o
worker de pé, os jobs só se acumulam — o esquecimento mais comum depois do primeiro
deploy.

## Renovação do certificado

```bash
certbot renew --dry-run
```

**Teste** a renovação, não confie que "deve funcionar sozinho". Certificado vencido
derruba o site inteiro de uma vez.

## Estado deste projeto

Sem VPS nem domínio: os arquivos estão versionados e revisáveis, mas **nenhum deploy foi
executado**. Declarado, não simulado.
