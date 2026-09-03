# Aula 04 — Namespaces e autoload PSR-4 com Composer

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/04-namespaces-psr4

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** [crm-produto/composer.json](../crm-produto/composer.json) · estrutura em [crm-produto/src/](../crm-produto/src/)

## Objetivo

Organizar o código em namespaces coerentes com as camadas do Módulo 1, e entender
como o autoload PSR-4 conecta namespace, pasta e nome de arquivo — sem "por que não
está achando minha classe" misterioso.

## Conceito

PSR-4 é especificação da **PHP-FIG**: o namespace mapeia para um caminho de pasta, e
o nome da classe mapeia para o nome do arquivo. É o que deixa ferramentas
encontrarem a classe sem `require` manual.

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

Tudo que começa com `App\` é procurado dentro de `src/`, trocando cada `\` por `/`.

| Namespace + classe | Caminho esperado |
|---|---|
| `App\Domain\Cliente\Cliente` | `src/Domain/Cliente/Cliente.php` |
| `App\Domain\Contato\Contato` | `src/Domain/Contato/Contato.php` |
| `App\Application\Cliente\CadastrarCliente` | `src/Application/Cliente/CadastrarCliente.php` |
| `App\Infrastructure\Cliente\RepositorioDeClientesPdo` | `src/Infrastructure/Cliente/RepositorioDeClientesPdo.php` |

Se o namespace declarado **dentro do arquivo** não bate com o caminho esperado, o
autoload falha com "classe não encontrada" — mesmo com o arquivo existindo
fisicamente. É a confusão clássica: o arquivo está lá, mas o PHP "não acha".

### Estrutura de namespace do CRM de produto

```
src/
├── Domain/
│   ├── ErroDeDominio.php            (namespace App\Domain)
│   ├── Cliente/
│   │   ├── Cliente.php              (namespace App\Domain\Cliente)
│   │   ├── StatusCliente.php
│   │   ├── ClienteInvalido.php
│   │   ├── ClienteNaoEncontrado.php
│   │   ├── EmailJaCadastrado.php
│   │   └── RepositorioDeClientes.php
│   ├── Contato/
│   │   ├── Contato.php
│   │   ├── CanalPreferido.php
│   │   ├── ContatoInvalido.php
│   │   └── RepositorioDeContatos.php
│   ├── Atividade/
│   │   ├── Atividade.php
│   │   ├── TipoAtividade.php
│   │   ├── AtividadeInvalida.php
│   │   └── RepositorioDeAtividades.php
│   ├── Usuario/Papel.php
│   ├── Notificacao/RemetenteDeEmail.php
│   └── Relatorio/GeradorDeRelatorio.php
├── Application/
│   ├── Cliente/{CadastrarCliente,ListarClientesAtivos}.php
│   ├── Contato/CadastrarContato.php
│   └── Atividade/RegistrarAtividade.php
└── Infrastructure/
    ├── Cliente/RepositorioDeClientesEmMemoria.php
    ├── Contato/RepositorioDeContatosEmMemoria.php
    ├── Atividade/RepositorioDeAtividadesEmMemoria.php
    ├── Notificacao/RemetenteDeEmailEmLog.php
    └── Relatorio/GeradorDeRelatorioCsv.php
```

Cada subpasta de `Domain/` é um **agregado** ou conceito central do negócio.
`Application/` e `Infrastructure/` espelham essa organização.

### Importando com `use`

```php
<?php

declare(strict_types=1);

namespace App\Application\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\RepositorioDeClientes;

final readonly class CadastrarCliente
{
    public function __construct(
        private RepositorioDeClientes $repositorio,
    ) {
    }

    public function executar(string $nome, string $email): Cliente
    {
        return $this->repositorio->salvar(Cliente::novo($nome, $email));
    }
}
```

Duas classes com o mesmo nome de namespaces diferentes → apelido com `as`:

```php
use App\Domain\Cliente\StatusCliente;
use App\Domain\Usuario\Papel as PapelDeUsuario;
```

### Autoload em ação

Um único `require` no bootstrap carrega todo o autoload:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Domain\Cliente\Cliente;

$cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');
```

Classe nova seguindo a convenção já funciona sem passo extra. Mas **editar o
mapeamento** em `composer.json` (namespace novo) exige regenerar:

```bash
composer dump-autoload
```

### Autoload de testes

```json
{
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

Um teste de `App\Domain\Cliente\Cliente` vive em `tests/Domain/Cliente/ClienteTest.php`
com `namespace Tests\Domain\Cliente;`. Espelhar `src/` facilita achar o teste de
qualquer classe — é assim que o projeto está.

## Na prática

### Exercício 1 — Diagnosticar erro de autoload

```
Fatal error: Uncaught Error: Class "App\Domain\Contato\Contato" not found
```

O arquivo existe em `src/Domain/Contatos/Contato.php` (plural) e declara
`namespace App\Domain\Contato;` (singular).

**Problema:** o namespace declarado não bate com o caminho real. PSR-4 espera
`App\Domain\Contato\Contato` em `src/Domain/Contato/Contato.php` — exatamente essa
capitalização, esse singular.

**Correção:** renomear a pasta para `Contato/`, ou ajustar o namespace para bater com
a pasta. Depois, `composer dump-autoload` (com PSR-4 puro a resolução é dinâmica por
convenção, mas vale regenerar após mudança estrutural).

### Exercício 2 — Organizar por camada

| Classe | Namespace | Arquivo |
|---|---|---|
| `Atividade` (entidade) | `App\Domain\Atividade\Atividade` | `src/Domain/Atividade/Atividade.php` |
| `RepositorioDeAtividades` (interface) | `App\Domain\Atividade\RepositorioDeAtividades` | `src/Domain/Atividade/RepositorioDeAtividades.php` |
| `RegistrarAtividade` (caso de uso) | `App\Application\Atividade\RegistrarAtividade` | `src/Application/Atividade/RegistrarAtividade.php` |
| `RepositorioDeAtividadesPdo` | `App\Infrastructure\Atividade\RepositorioDeAtividadesPdo` | `src/Infrastructure/Atividade/RepositorioDeAtividadesPdo.php` |

Note que a **interface** fica junto da entidade, no domínio; a **implementação** vai
para infraestrutura. É a inversão de dependência aplicada à estrutura de pastas.

## Pontos de atenção

- **Capitalização diferente entre namespace e pasta** — em sistema de arquivos
  case-insensitive (Windows) "funciona" na sua máquina e **quebra em produção**
  (Linux, case-sensitive).
- **Mais de uma classe por arquivo** — PSR-4 assume uma classe por arquivo, com o
  mesmo nome. Não é estética, é requisito do autoload.
- **Esquecer `composer dump-autoload`** depois de mudar `composer.json` (não é preciso
  ao criar arquivo novo que já segue a convenção).
- **Namespace genérico demais** (`App\Models`, `App\Helpers`, `App\Utils` guarda-tudo)
  — sintoma de falta de organização por camada/domínio real.
- **Confundir PSR-4 com PSR-0** (obsoleto, usa underscore como separador). O curso usa
  exclusivamente PSR-4.

## Entrega da aula

- [x] Classes organizadas em `Domain/`, `Application/`, `Infrastructure/`
- [x] Cada namespace batendo exatamente com o caminho relativo a `src/`
- [x] `composer dump-autoload` + `composer quality` — tudo funcionando
- [x] Testes em `tests/` espelhando a estrutura, com namespace `Tests\...`

## Aplicar no CRM de produto

Regra de bolso ao criar qualquer classe: **decidir a camada primeiro** (é regra de
negócio? orquestração? detalhe técnico?), e o caminho do arquivo cai por consequência.
