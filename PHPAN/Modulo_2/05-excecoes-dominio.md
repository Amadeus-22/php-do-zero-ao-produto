# Aula 05 — Exceções de domínio: erros que o produto entende

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/05-excecoes-dominio

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** [ErroDeDominio.php](../crm-produto/src/Domain/ErroDeDominio.php) ·
[ClienteInvalido.php](../crm-produto/src/Domain/Cliente/ClienteInvalido.php) ·
[ClienteNaoEncontrado.php](../crm-produto/src/Domain/Cliente/ClienteNaoEncontrado.php) ·
[EmailJaCadastrado.php](../crm-produto/src/Domain/Cliente/EmailJaCadastrado.php)

## Objetivo

Trocar `die()`, `var_dump()` e mensagens genéricas por **exceções de domínio** —
classes próprias que carregam significado de negócio e permitem tratamento
diferenciado em cada camada.

## Conceito

### O problema com erro genérico

```php
if (!$emailValido) {
    die('Erro: e-mail inválido');
}
```

Quebra em produto real por três razões:

1. `die()`/`exit()` **encerra o processo inteiro** — inaceitável numa API que deveria
   seguir servindo, ou num teste que precisa capturar o erro.
2. A mensagem é **string solta** — quem chama não consegue distinguir
   programaticamente "e-mail inválido" de "cliente não encontrado" sem
   `str_contains($mensagem, '...')`, que é frágil.
3. **Não existe hierarquia** — não dá para dizer "capture qualquer erro de validação"
   de forma estruturada.

### Exceções como parte do domínio

Cada erro relevante do negócio vira **classe própria**, estendendo uma exceção nativa
apropriada (`\InvalidArgumentException`, `\DomainException`, `\RuntimeException`) ou
uma base do seu domínio.

```php
final class ClienteNaoEncontrado extends \DomainException
{
    public static function comId(int $id): self
    {
        return new self("Cliente com ID {$id} não foi encontrado.");
    }

    public static function comEmail(string $email): self
    {
        return new self("Cliente com e-mail {$email} não foi encontrado.");
    }
}
```

```php
final class EmailJaCadastrado extends \DomainException
{
    public function __construct(
        private readonly string $email,
    ) {
        parent::__construct("O e-mail {$this->email} já está cadastrado.");
    }

    public function email(): string
    {
        return $this->email;
    }
}
```

Detalhe importante: como o construtor **promove** `$email` como propriedade, ela já
existe quando `parent::__construct()` é chamado logo em seguida, então dá para usá-la
na interpolação.

> **Erro comum:** esquecer `parent::__construct()`. Sem ele, `getMessage()` volta
> **vazio** — a mensagem nunca chega à classe base.

### Hierarquia de exceções

Uma base do domínio permite capturar "qualquer erro esperado do CRM" de forma
agrupada:

```php
<?php

declare(strict_types=1);

namespace App\Domain;

abstract class ErroDeDominio extends \DomainException
{
}
```

E a apresentação captura de forma escalonada:

```php
try {
    $cliente = $cadastrarCliente->executar($nome, $email);
} catch (EmailJaCadastrado $e) {
    http_response_code(409); // Conflict
    echo json_encode(['erro' => $e->getMessage()]);
} catch (ErroDeDominio $e) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['erro' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['erro' => 'Erro interno. Tente novamente.']);
}
```

**A ordem importa:** mais específica primeiro (`EmailJaCadastrado`), depois a genérica
do domínio (`ErroDeDominio`), depois o não previsto (`\Throwable`) — que nunca deve
vazar detalhe interno ao usuário, só ser logado.

### Erro de domínio × erro de infraestrutura × bug

| Tipo | Exemplo | Onde tratar |
|---|---|---|
| **Domínio** (esperado, regra de negócio) | E-mail já cadastrado, cliente inativo tentando comprar | Aplicação/apresentação, mensagem amigável |
| **Infraestrutura** (inesperado, técnico) | Banco fora do ar, timeout de rede | Log + mensagem genérica ao usuário, alerta para investigar |
| **Programação** (bug) | `TypeError`, `ArgumentCountError` | Nunca deveria chegar em produção; PHPStan + testes pegam antes |

### Traduzindo erro técnico em erro de domínio

```php
public function salvar(Cliente $cliente): Cliente
{
    try {
        $stmt = $this->pdo->prepare(
            'INSERT INTO clientes (nome, email) VALUES (:nome, :email)'
        );
        $stmt->execute([
            'nome' => $cliente->nome(),
            'email' => $cliente->email(),
        ]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] === 1062) { // código MySQL de chave duplicada
            throw new EmailJaCadastrado($cliente->email());
        }

        throw $e; // erro de infraestrutura genuíno — deixa subir
    }

    // ...
}
```

A infraestrutura sabe interpretar o erro específico do banco; o domínio não precisa
saber que MySQL existe.

## Na prática — exceções para Atividade e Contato

No projeto ficaram como fábricas nomeadas agrupadas por entidade, em vez de uma classe
por mensagem:

```php
final class AtividadeInvalida extends ErroDeDominio
{
    public static function descricaoVazia(): self
    {
        return new self('Descrição da atividade é obrigatória.');
    }

    public static function clienteInexistente(int $clienteId): self
    {
        return new self("Não é possível registrar atividade: cliente {$clienteId} não existe.");
    }
}
```

```php
final class ContatoInvalido extends ErroDeDominio
{
    public static function nomeVazio(): self
    {
        return new self('Nome do contato é obrigatório.');
    }

    public static function emailInvalido(string $email): self
    {
        return new self("E-mail de contato inválido: {$email}");
    }
}
```

## Pontos de atenção

- **`\Exception` genérica para tudo** — perde a captura seletiva por tipo.
- **Mensagem técnica vazando para o usuário** — "SQLSTATE[23000]: Integrity
  constraint violation" não pode aparecer numa tela de formulário.
- **Engolir exceção em silêncio** (`catch (\Throwable $e) {}` vazio) — esconde bug
  real. Sempre logue, mesmo decidindo não interromper o fluxo.
- **Exceção para controle de fluxo normal** — se "não encontrado" é situação esperada
  e comum (busca opcional), retorne `null`. Exceção é para o que **impede** a operação
  de continuar.
- **`@throws` ausente no PHPDoc** de método público importante — documentar ajuda quem
  consome seu código (e o PHPStan com plugins).

## Entrega da aula

- [x] Base `App\Domain\ErroDeDominio` estendendo `\DomainException`
- [x] Exceções específicas com fábricas nomeadas: `ClienteInvalido`,
      `ClienteNaoEncontrado`, `EmailJaCadastrado`, `ContatoInvalido`,
      `AtividadeInvalida`
- [x] Lançadas nos pontos reais de validação (fábrica `Cliente::novo()`, construtores
      de `Contato`/`Atividade`, casos de uso)
- [x] Testes com `expectException(...)` verificando que a exceção certa sai na
      condição certa
- [x] `composer quality`

## Aplicar no CRM de produto

O mapa exceção → status HTTP já está desenhado para o Módulo 4 (API):

| Exceção | Status |
|---|---|
| `EmailJaCadastrado` | 409 Conflict |
| `ClienteNaoEncontrado` | 404 Not Found |
| `ClienteInvalido` / `ContatoInvalido` / `AtividadeInvalida` | 422 Unprocessable Entity |
| Qualquer `\Throwable` não previsto | 500 + log, mensagem genérica |
