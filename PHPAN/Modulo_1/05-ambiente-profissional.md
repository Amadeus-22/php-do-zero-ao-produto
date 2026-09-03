# Aula 05 — Ambiente profissional: PHP 8.3, Composer no fluxo diário

**Curso:** PHPAN (intermediário) · **Módulo 1** — Mapa do intermediário e revisão ativa
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/05-ambiente-profissional

Status: [x] assistida · [x] anotada · [x] praticada

## Objetivo

Configurar o **starter técnico do curso** (`php-profissional-base`): PHP 8.3,
Composer, PHPUnit e PHPStan — rodando todo santo dia, não como "configuração que se
faz uma vez e esquece".

## Conceito

Um produto profissional tem um **fluxo de trabalho**: um punhado de comandos rodados
quase no automático, que garantem três coisas:

1. **O código roda** — dependências instaladas via Composer.
2. **O código funciona como esperado** — testes automatizados via PHPUnit.
3. **O código não tem erro óbvio antes mesmo de rodar** — análise estática via PHPStan.

Instalar, testar, analisar. O hábito se instala agora; a profundidade vem ao longo
do curso.

### Composer não é só "baixar biblioteca"

O segundo papel, tão importante quanto: **autoload PSR-4**. É o que permite escrever

```php
use App\Domain\Cliente\Cliente;
```

em vez de

```php
require_once __DIR__ . '/../../src/Domain/Cliente/Cliente.php';
```

O mapeamento `App\` → `src/` já vem no `composer.json` do starter:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

**A regra que decorre disso:** a classe `App\Domain\Cliente\Cliente` *precisa* estar
em `src/Domain/Cliente/Cliente.php`. Nome da classe e caminho do arquivo andam
juntos — não é estética, é como o autoload encontra a classe sem `require` manual.
PSR-4 formalmente no Módulo 2, aula 4.

### Os scripts de qualidade

```json
{
    "scripts": {
        "test": "vendor/bin/phpunit tests",
        "analyse": "vendor/bin/phpstan analyse",
        "style:check": "vendor/bin/php-cs-fixer fix --dry-run --diff",
        "style:fix": "vendor/bin/php-cs-fixer fix",
        "quality": [
            "@style:check",
            "@analyse",
            "@test"
        ]
    }
}
```

`composer quality` roda em sequência: estilo → análise estática → testes. Se alguma
etapa falha, para ali e reporta.

> **Este é o comando que roda antes de considerar qualquer aula concluída, daqui em
> diante.**

## Na prática

### Passo 1 — Confirmar a versão do PHP

```bash
php -v
```

Piso: **PHP 8.3** (`composer.json` exige `"php": "^8.3"`). O curso usa recursos de
8.1+ (enums, readonly).

### Passo 2 — Instalar dependências

Dentro da cópia do starter feita na Aula 4 (`crm-produto/`):

```bash
composer install
```

Lê o `composer.lock` e instala as versões travadas de PHPUnit, PHPStan e PHP-CS-Fixer
em `vendor/`. **Nunca** editar `vendor/` à mão nem versioná-la no Git — é gerada, e o
`.gitignore` do starter já a ignora.

### Passo 3 — Rodar a suíte de qualidade

```bash
composer quality
```

Na primeira execução deve passar limpo — o starter vem com `src/Example.php` e
`src/HealthCheck.php`, cada um com teste correspondente. Se falhar **antes** de você
escrever qualquer linha própria, o problema é de ambiente (versão de PHP, extensão
faltando), não do seu código. Resolver agora, não deixar acumular.

### Passo 4 — Entender o que o PHPStan checa

`phpstan.neon`:

```yaml
parameters:
	level: 5
	paths:
		- src
		- tests
```

`level: 5` é rigor intermediário (escala 0 a 9+). Pega, por exemplo: chamada de
método em tipo possivelmente `null`, argumento com tipo incompatível, retorno que não
bate com a assinatura declarada.

```bash
composer analyse
```

**Experimento recomendado:** quebrar de propósito um arquivo pequeno para ver o
PHPStan reclamar. Ex.: em `src/HealthCheck.php`, trocar o tipo de retorno de
`status()` para `int` (errado — ele retorna array) e rodar `composer analyse` de
novo. Desfazer depois (`git checkout -- src/HealthCheck.php`).

**Feito no projeto — saída real:**

```
  Line   src/HealthCheck.php
 ------ -----------------------------------------------------------------------
  13     PHPDoc tag @return with type array<string, string> is incompatible
         with native type int.                          🪪 return.phpDocType
  15     Method App\HealthCheck::status() should return int but returns
         array<string, string>.                         🪪 return.type

  Line   tests/HealthCheckTest.php
 ------ ---------------------------------------
  16     Cannot access offset 'status' on int.  🪪 offsetAccess.nonOffsetAccessible
  17     Cannot access offset 'php' on int.     🪪 offsetAccess.nonOffsetAccessible
```

Duas coisas para levar dessa saída:

1. Ele pegou **dois níveis**: a incompatibilidade dentro da classe **e** a propagação
   para quem consome (`tests/HealthCheckTest.php`). Uma mudança de assinatura errada
   é apontada em todos os lugares que ela quebraria — antes de rodar.
2. O PHPDoc `@return array{...}` não é decoração: foi ele que gerou o primeiro erro.
   Tipo nativo e PHPDoc precisam contar a mesma história.

### Passo 5 — Rodar testes isoladamente

```bash
composer test
```

Olhar `tests/HealthCheckTest.php` para ver o formato de um teste PHPUnit básico — os
seus começam no Módulo 2.

### Passo 6 (opcional) — Docker

O starter inclui `Dockerfile` e `compose.yaml`, úteis se a máquina não tem PHP 8.3 ou
para isolar o ambiente:

```bash
docker compose up -d
docker compose exec app composer install
```

Não é obrigatório. O Módulo 7 (Produção) volta ao tema com profundidade.

## Pontos de atenção

- **Rodar `composer install` uma vez e nunca mais rodar `composer quality`** — o
  hábito só vale se for hábito. Toda aula prática termina com ele rodando limpo.
- **Editar arquivos dentro de `vendor/`** — se perde no próximo `composer install`.
  Precisar mudar comportamento de uma dependência é sinal de outro problema (lib
  errada, ou falta uma camada de adaptação sua).
- **Ignorar erro do PHPStan "porque o código roda mesmo assim"** — o propósito da
  ferramenta é pegar erro *antes* de rodar. Ignorar sistematicamente destrói o valor
  de tê-la.
- **Achar `composer.lock` opcional** — ele **deve** ser versionado. É o que garante
  que você, colegas e CI rodam exatamente as mesmas versões.
- **Misturar a pasta do curso com a do projeto** — `curso/` é material de estudo
  (este markdown). O código vive em `crm-produto/`.

## Entrega da aula

- [x] `php -v` confirma PHP 8.3+ — **PHP 8.4.25**
- [x] `composer install` sem erro dentro do projeto ([crm-produto/](../crm-produto/), Composer 2.9.5)
- [x] `composer quality` passando limpo (estilo 0 · PHPStan level 5 sem erro · 25 testes / 48 asserções)
- [x] Erro do PHPStan reproduzido de propósito (passo 4), saída registrada acima,
      alteração desfeita
- [ ] `composer.lock` commitado — *pendente: a pasta ainda não é repositório Git*

## Aplicar no CRM de produto

Fluxo diário, daqui até o fim do curso:

```bash
composer install     # uma vez, e sempre que o lock mudar
composer test        # durante o desenvolvimento
composer analyse     # antes de fechar a aula
composer quality     # portão final — tem que passar limpo
```

**Fim do Módulo 1.** Sei o que muda de nível, revisei a base, entendo camadas, li o
briefing e o ambiente roda. O Módulo 2 começa a construir o domínio do CRM com OOP
séria.
