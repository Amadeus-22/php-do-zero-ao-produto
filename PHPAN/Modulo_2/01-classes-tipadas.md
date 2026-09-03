# Aula 01 — Classes, propriedades tipadas e métodos com intenção clara

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/01-classes-tipadas

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** [crm-produto/src/Domain/Cliente/Cliente.php](../crm-produto/src/Domain/Cliente/Cliente.php) ·
[StatusCliente.php](../crm-produto/src/Domain/Cliente/StatusCliente.php) ·
teste em [tests/Domain/Cliente/ClienteTest.php](../crm-produto/tests/Domain/Cliente/ClienteTest.php)

## Objetivo

Classes de domínio com **tipo explícito em tudo** — propriedades, parâmetros,
retornos — e métodos cujo nome comunica intenção de negócio, não implementação.

## Conceito

PHP tem tipagem gradual: dá para escrever sem tipo nenhum, e "funciona" até o dia em
que um `null` inesperado chega onde não devia e explode em produção. Tipagem aqui é a
primeira linha de defesa, e é o que permite ao PHPStan pegar erro antes de rodar.

### As três coisas que "classe tipada" significa

1. **Propriedades com tipo** — nunca `public $nome;`, sempre `private string $nome;`.
2. **Parâmetros e retornos tipados** em todo método público.
3. **`declare(strict_types=1)`** no topo de todo arquivo — desliga a coerção
   implícita, fazendo o PHP reclamar se `"10"` (string) chegar onde se espera `int`.

```php
<?php

declare(strict_types=1); // sem isso, PHP converteria "10" para int(10) silenciosamente
```

Sem `strict_types`, o PHP tenta ser "flexível" e esconde bugs. Com ele, o erro aparece
como `TypeError` **no lugar certo**, em vez de se manifestar 3 camadas depois.

### Property promotion — construtor enxuto

```php
// Estilo antigo (repetitivo)
final class Cliente
{
    private int $id;
    private string $nome;

    public function __construct(int $id, string $nome)
    {
        $this->id = $id;
        $this->nome = $nome;
    }
}
```

```php
// Estilo com property promotion (PHP 8+) — padrão do curso inteiro
final class Cliente
{
    public function __construct(
        private int $id,
        private string $nome,
    ) {
    }
}
```

Equivalentes em comportamento.

### `readonly` — imutabilidade onde faz sentido

PHP 8.1+: propriedade `readonly` só pode ser atribuída no construtor (ou no escopo
declarante), nunca reatribuída.

```php
final class Cliente
{
    public function __construct(
        private readonly ?int $id,
        private string $nome,
        private readonly string $email,
    ) {
    }

    public function renomear(string $novoNome): void
    {
        $novoNome = trim($novoNome);

        if ($novoNome === '') {
            throw new \InvalidArgumentException('Nome não pode ficar vazio.');
        }

        $this->nome = $novoNome;
    }
}
```

A escolha é deliberada: `$id` e `$email` são `readonly` (mudar e-mail de cliente é uma
operação nova, não reatribuição livre). `$nome` tem `renomear()`, que **revalida a
cada mudança**.

> **Mutação sem controle é o problema, não mutação em si.** Quando precisa mudar um
> valor, faça por um método que reafirma as regras — nunca por atribuição pública.

### Tipos que valem conhecer bem

| Tipo | Uso típico no domínio do CRM |
|---|---|
| `?int`, `?string` (nullable) | `id` antes de persistir (`null` = ainda não salvo) |
| `array` com PHPDoc (`@param list<Contato>`) | Coleções — PHP não tem generics; o PHPDoc alimenta PHPStan/IDE |
| `self` / `static` | Retorno de métodos de fábrica (`Cliente::novo(...)`) |
| `\DateTimeImmutable` | Qualquer data/hora do domínio — nunca `\DateTime` mutável |
| `enum` (backed) | Conjuntos fechados de valores (papel, status, canal) |

## Na prática

A versão definitiva de `Cliente`, com tudo junto, está implementada e testada em
[crm-produto/src/Domain/Cliente/Cliente.php](../crm-produto/src/Domain/Cliente/Cliente.php).
Dois pontos de design merecem atenção:

**1. Construtor `private` + duas fábricas nomeadas.**

- `novo()` — cliente criado pela primeira vez pela aplicação: valida tudo e define
  `criadoEm` como agora.
- `reconstituir()` — usado pela infraestrutura ao carregar um cliente que **já
  existe** no banco. Não revalida regras de criação: o dado já passou por elas quando
  foi salvo.

Separar isso evita, por exemplo, tentar validar `criadoEm` como "agora" para um
registro de 3 anos atrás. (Há um teste exatamente para esse caso:
`testReconstituirNaoRevalidaRegrasDeCriacao`.)

**2. Nenhum setter genérico.**

Não existe `setNome()`, existe `renomear()`. Não existe `setStatus()`, existe
`desativar()`. O nome comunica **o que aconteceu no negócio**:

```php
$cliente->setStatus('inativo');   // ruim: descreve troca de campo
$cliente->desativar();            // bom: descreve o fato de negócio
```

## Pontos de atenção

- **Esquecer `declare(strict_types=1)`** em arquivo novo — vira inconsistência
  silenciosa. Vale configurar o editor para inserir automaticamente. (O
  `.php-cs-fixer.dist.php` do projeto tem a regra `declare_strict_types`, então o
  `composer style:check` pega.)
- **Deixar propriedade `public` "para simplificar"** — é exatamente o hábito do
  PHPIAN que este módulo existe para quebrar.
- **Criar setter para tudo** — `setNome()`, `setEmail()`, `setStatus()` genéricos
  reintroduzem invariantes quebráveis.
- **Confundir nullable com "não obrigatório na prática"** — `?int $id` significa "pode
  ser `null`", e o código que consome precisa tratar os dois casos. O PHPStan reclama
  se você usar um `?int` sem checar `null` antes.
- **`\DateTime` mutável em vez de `\DateTimeImmutable`** — `\DateTime` deixa qualquer
  código que receba a referência alterar a data por baixo dos panos.

## Entrega da aula

- [x] `src/Domain/Cliente/Cliente.php` e `src/Domain/Cliente/StatusCliente.php`
      criados, com construtor privado, fábricas nomeadas e sem setters genéricos
- [x] `composer analyse` sem erro nos arquivos novos
- [x] Teste em `tests/Domain/Cliente/ClienteTest.php` cobrindo: `novo()` com nome
      vazio lança, `novo()` com e-mail inválido lança, `renomear()` funciona e revalida
- [x] `composer quality` de ponta a ponta passando

## Aplicar no CRM de produto

Padrão fixado para toda entidade daqui em diante: construtor privado + `novo()` +
`reconstituir()` + getters sem `get` + métodos de intenção no lugar de setters.
