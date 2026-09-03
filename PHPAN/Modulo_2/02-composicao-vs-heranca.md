# Aula 02 — Encapsulamento, composição vs herança: quando usar cada um

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/02-composicao-vs-heranca

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** [crm-produto/src/Domain/Usuario/Papel.php](../crm-produto/src/Domain/Usuario/Papel.php) ·
[Contato.php](../crm-produto/src/Domain/Contato/Contato.php) (composição: Contato→CanalPreferido)

## Objetivo

Saber quando cada uma é a ferramenta certa, e evitar a armadilha clássica de
iniciante-avançado: hierarquias de herança frágeis que quebram a cada requisito novo.

## Conceito

### Encapsulamento, com mais peso

Esconder o estado interno e expor só o necessário via métodos. O objetivo não é
"esconder por esconder" — é **impedir que o objeto entre em estado inválido** porque
código externo mexeu numa propriedade sem passar pelas regras.

```php
// Sem encapsulamento — qualquer código pode quebrar a invariante
final class ContaComSaldo
{
    public float $saldo = 0.0;
}

$conta = new ContaComSaldo();
$conta->saldo = -500.0; // nada impede isso
```

```php
// Com encapsulamento — a regra é protegida
final class ContaComSaldo
{
    private float $saldo = 0.0;

    public function depositar(float $valor): void
    {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('Depósito deve ser positivo.');
        }

        $this->saldo += $valor;
    }

    public function saldo(): float
    {
        return $this->saldo;
    }
}
```

### Herança: "é um" (is-a)

Relação de **especialização**: a subclasse é um tipo mais específico da superclasse.
Onde se espera um `Usuario`, um `Administrador` serve.

```php
<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

abstract class Usuario
{
    public function __construct(
        protected readonly int $id,
        protected readonly string $nome,
        protected readonly string $email,
    ) {
    }

    public function nome(): string
    {
        return $this->nome;
    }

    abstract public function podeGerenciarUsuarios(): bool;
}

final class Administrador extends Usuario
{
    public function podeGerenciarUsuarios(): bool
    {
        return true;
    }
}

final class Vendedor extends Usuario
{
    public function podeGerenciarUsuarios(): bool
    {
        return false;
    }
}
```

`protected` (não `private`) nas propriedades da base: subclasses precisam acessar,
código externo não. **Esse é o único uso legítimo de `protected` no dia a dia** —
comunicação controlada entre classe-base e subclasses.

### Composição: "tem um" (has-a)

Relação de posse/colaboração: um objeto **contém ou usa** outro sem ser um tipo dele.
Um `Cliente` não é um `Endereco` — ele **tem** um.

```php
final class Endereco
{
    public function __construct(
        private readonly string $logradouro,
        private readonly string $cidade,
        private readonly string $uf,
        private readonly string $cep,
    ) {
    }

    public function formatado(): string
    {
        return "{$this->logradouro}, {$this->cidade}/{$this->uf} - {$this->cep}";
    }
}

final class Cliente
{
    public function __construct(
        private readonly ?int $id,
        private string $nome,
        private readonly string $email,
        private ?Endereco $endereco = null,
    ) {
    }

    public function definirEndereco(Endereco $endereco): void
    {
        $this->endereco = $endereco;
    }

    public function endereco(): ?Endereco
    {
        return $this->endereco;
    }
}
```

### Por que composição costuma ganhar

*Favor composition over inheritance* tem razão concreta: herança cria **acoplamento
rígido em tempo de compilação**. Se a base muda, toda subclasse é afetada — às vezes
de formas inesperadas. Composição permite trocar comportamento em tempo de execução,
sem hierarquia fixa.

O exemplo clássico do problema — herança usada para **reaproveitar código**:

```php
// Problemático
class RepositorioBase
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function executar(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

class RepositorioDeClientes extends RepositorioBase { /* ... */ }
class RepositorioDeContatos extends RepositorioBase { /* ... */ }
```

"Funciona", mas `RepositorioDeClientes` **não é um tipo de** `RepositorioBase` — ele
só queria reaproveitar `executar()`. Isso é reuso disfarçado de herança. Com
composição:

```php
final class ConexaoSql
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function executar(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

final class RepositorioDeClientesPdo implements RepositorioDeClientes
{
    public function __construct(
        private readonly ConexaoSql $conexao,
    ) {
    }

    public function salvar(Cliente $cliente): Cliente
    {
        $this->conexao->executar('INSERT INTO clientes ...', [/* ... */]);
        // ...
    }
}
```

Agora o repositório **usa** uma `ConexaoSql`. Se amanhã quiser logar cada query, muda
só `ConexaoSql` — sem tocar em repositório nenhum, sem risco de quebrar uma cadeia.

### Quando herança ainda é certa

- Relação **"é um"** genuína e **estável** (não muda com o próximo requisito).
- Subclasses compartilham **comportamento**, não só dados.
- Variação **fechada** de tipos.

> Ainda assim, no CRM deste curso papéis viram `enum Papel` e não árvore de classes —
> papéis tendem a ser dados + comportamento simples.

## Na prática — exercício de decisão

| Par | Resposta |
|---|---|
| `Contato` e `Cliente` | **Composição.** `Cliente` tem uma coleção de `Contato`, não é um tipo de `Contato`. |
| `AdministradorDoSistema` e `Usuario` | **Herança** (ou papel/enum). Se optar por classes, herança é defensável: especialização estável. |
| `Atividade` e `TipoAtividade` | **Composição.** E `TipoAtividade` é melhor como `enum` — conjunto fechado e simples de valores. |
| `RelatorioEmPdf` e `RelatorioEmCsv` | **Nem uma nem outra**: o padrão certo é uma **interface** `GeradorDeRelatorio` implementada por cada formato, com uma classe que **usa** qualquer implementação. Formalizado na aula 3. |

## Pontos de atenção

- **Herança para reaproveitar código** ("essas duas classes têm método parecido, vou
  pôr numa mãe") sem relação "é um" real — antipadrão mais comum. Extraia uma classe
  colaboradora.
- **Hierarquias de mais de 2 níveis** (`A extends B extends C extends D`) — cada nível
  soma acoplamento. Em 3+ níveis quase sempre dá para achatar com composição +
  interfaces.
- **Sobrescrever método da base mudando o comportamento esperado** (violação de
  Liskov) — se `Vendedor::podeGerenciarUsuarios()` lança exceção em vez de retornar
  `bool`, todo código que trata `Usuario` genericamente quebra.
- **`protected` como "public disfarçado"** para contornar encapsulamento — só se
  justifica com herança real.

## Entrega da aula

- [x] Uma relação de composição real implementada: `Contato` **tem um**
      `CanalPreferido` (enum); `Atividade` **tem um** `TipoAtividade`
- [x] Decisão registrada (abaixo)
- [x] `composer quality`

**Por que não usei herança aqui:** `Contato` e `Atividade` não são especializações de
`CanalPreferido`/`TipoAtividade` — eles *possuem* um valor de um conjunto fechado.
Modelar isso como enum dá exaustividade no `match` (o PHP obriga a cobrir todos os
casos) sem criar uma árvore de classes que precisaria mudar a cada canal novo.

**Relação "é um" genuína no domínio:** por ora, nenhuma. `Papel` foi modelado como
`enum` com `podeEditar()`/`podeGerenciarUsuarios()` em vez de
`Administrador extends Usuario`, porque o comportamento que diferencia os papéis cabe
em dois predicados — uma hierarquia de classes aqui seria peso sem ganho.

## Aplicar no CRM de produto

Regra prática adotada: **nenhuma herança no domínio** até aparecer um caso "é um"
estável de verdade. Reuso resolve-se com classe colaboradora + injeção.
