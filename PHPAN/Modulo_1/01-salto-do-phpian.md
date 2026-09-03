# Aula 01 — O salto do PHPIAN: o que muda no dia a dia

**Curso:** PHPAN (intermediário) · **Módulo 1** — Mapa do intermediário e revisão ativa
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/01-salto-do-phpian

Status: [x] assistida · [x] anotada · [ ] praticada *(falta a entrega escrita, ver no fim)*

## Objetivo

Deixar claro o que separa quem **sabe PHP** (PHPIAN) de quem constrói e mantém um
**produto** em PHP (PHPAN). O curso não vai reensinar sintaxe — muda o que importa.

## Conceito

A diferença não é "PHP mais difícil". É PHP sob **restrições de produto real**:

> "funciona na minha máquina, para um usuário, sem ninguém mexendo depois"
> **vs.**
> "funciona em produção, para vários usuários e papéis, e outras pessoas — inclusive
> você em 6 meses — vão mexer nesse código"

| Dimensão | PHPIAN (Mini CRM) | PHPAN (CRM de produto) |
|---|---|---|
| Estrutura | Arquivos soltos, mistura de HTML/SQL/PHP | Camadas: Domínio, Serviço, Controller, View/API |
| Banco | PDO direto na página | Repositórios, migrações, transações |
| Auth | Sessão simples, um tipo de usuário | Sessão + token de API, múltiplos papéis |
| Entrada de dados | `$_POST` sem muita validação | Validação centralizada, erros consistentes |
| Erros | `var_dump`, `die()`, warnings visíveis | Exceções de domínio, logs estruturados, páginas de erro decentes |
| Ambiente | Só "no ar" | Local, staging e produção, cada um com sua config |
| Qualidade | "Testei clicando" | Testes automatizados (PHPUnit), análise estática (PHPStan) |
| Manutenção | Você mesmo, sempre | Qualquer dev entende e altera sem medo |

**A observação central:** nenhuma linha dessa tabela exige aprender sintaxe nova.
É tudo **decisão de engenharia** — onde cada responsabilidade mora, como você se
protege de erro, como garante que uma mudança não quebra o resto.

### Por que ainda sem framework

Aprender Laravel antes de entender **por que** um framework existe ensina a decorar
convenções, não a projetar sistemas. O PHPAN faz você construir na mão o que o
framework faria: roteador, container simples, camada de validação, middleware.
Depois, ao usar Laravel ou Symfony, você sabe o que acontece por baixo — e consegue
debugar, não só copiar a documentação.

### "Produto" muda a régua

Um exercício tolera gambiarra porque ninguém depende dele amanhã. Um produto não:

- tem usuário real (mesmo que seja você e 3 clientes de teste);
- vai evoluir — feature nova, bug corrigido, campo novo na tabela;
- idealmente gera receita, então "no ar quebrado" tem custo.

Código que "funciona" deixa de ser suficiente. Passa a valer: **funciona, é seguro
contra falha básica, e outra pessoa consegue mexer.**

## Na prática

### Antes — estilo Mini CRM (tudo em um arquivo)

```php
<?php
// clientes.php — estilo Mini CRM (PHPIAN)
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=minicrm', 'root', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $pdo->exec("INSERT INTO clientes (nome, email) VALUES ('$nome', '$email')");
}

$clientes = $pdo->query('SELECT * FROM clientes')->fetchAll();
?>
<h1>Clientes</h1>
<ul>
<?php foreach ($clientes as $c): ?>
    <li><?= $c['nome'] ?> - <?= $c['email'] ?></li>
<?php endforeach; ?>
</ul>
```

Resolveu o problema no PHPIAN — mas tem SQL injection, mistura camadas e não escala.

### Depois — a mesma responsabilidade em camadas

**Domínio** — a entidade, sem saber nada de banco:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

final class Cliente
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private string $email,
    ) {
    }

    public static function novo(string $nome, string $email): self
    {
        return new self(id: null, nome: $nome, email: $email);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }
}
```

**Contrato de persistência** — uma interface, não uma implementação:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

interface RepositorioDeClientes
{
    public function salvar(Cliente $cliente): Cliente;

    /** @return list<Cliente> */
    public function todos(): array;
}
```

**Aplicação** — o caso de uso, com as regras do negócio:

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
        $nome = trim($nome);

        if ($nome === '') {
            throw new \InvalidArgumentException('Nome do cliente é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('E-mail inválido.');
        }

        return $this->repositorio->salvar(Cliente::novo($nome, $email));
    }
}
```

**Nenhum SQL aparece aqui.** A camada de aplicação não sabe se os dados vão para
MySQL, SQLite ou um arquivo. Essa é a virada de mentalidade do PHPAN: separar
**o que o negócio faz** de **como isso é persistido e exibido**.

As camadas são construídas de verdade a partir do Módulo 2 (OOP) e Módulo 3 (MVC).
Por ora, basta reconhecer o padrão.

## Pontos de atenção

Recursos de PHP 8 que o código "depois" usa e vale ter na ponta da língua:

- **Promoção de propriedades no construtor** (`private string $nome` direto na
  assinatura) — some o boilerplate de atribuição.
- **Argumentos nomeados** (`new self(id: null, ...)`) — legibilidade em construtores
  com vários parâmetros.
- **`readonly`** na classe inteira — o serviço não muda de estado depois de criado.
- **`final`** — sinaliza que não é ponto de extensão; herança aqui seria acidente.
- **`declare(strict_types=1)`** em todo arquivo — sem coerção silenciosa de tipo.
- **`/** @return list<Cliente> *\/`** — o PHPStan lê isso; a tipagem nativa do PHP
  só diria `array`.

Defeitos concretos do código "antes", para saber o que procurar no próprio código:

- **SQL injection** — `$_POST` interpolado direto na string do `INSERT`.
- **XSS** — `<?= $c['nome'] ?>` sem `htmlspecialchars`.
- **Credencial hardcoded** — `'root', ''` no meio da página.
- **Sem tratamento de erro** — qualquer falha do PDO vira warning na tela do usuário.

### Armadilhas apontadas na aula

- **Achar que precisa aprender "mais PHP"** — o gargalo é design, não sintaxe. Se
  estiver enferrujado, revise `match`, `enum`, `readonly` e tipos union/nullable,
  mas não é isso que trava.
- **Pular para o framework "para ir mais rápido"** — adia o entendimento e cria
  dependência de mágica que você não controla.
- **Comparar o Mini CRM com o CRM de produto e se sentir mal** — objetivos
  diferentes por design: um ensina fundamento rápido, o outro sobrevive a mudanças.
- **Achar que "produto" = "muito código"** — é sobre onde a responsabilidade mora.
  Projeto pequeno bem separado em camadas > projeto grande num arquivo só.

## Entrega da aula

Sem código. Por escrito:

1. Listar 3 coisas do Mini CRM do PHPIAN que eram gambiarra (SQL na view, sem
   validação, sem tratamento de erro…).
2. Para cada uma, 1 frase com o conceito do PHPAN que provavelmente resolve.

Vira insumo para a **Aula 04 — Briefing do CRM de produto**.

| # | Gambiarra no Mini CRM | Hipótese de solução no PHPAN |
|---|---|---|
| 1 | | |
| 2 | | |
| 3 | | |

> O Mini CRM está em `PHPIAN/Modulo_8(modeagem_final)/mini-crm/` — dá para abrir
> `src/contatos.php` e `public/contatos/criar.php` e apontar os pontos reais.

## Aplicar no CRM de produto

- O alvo do curso é `PHPAN/Modulo_8/crm-produto/`, evolução do `mini-crm`.
- Estrutura que a aula já antecipa: `App\Domain\<Contexto>` (entidades + interfaces
  de repositório) e `App\Application\<Contexto>` (casos de uso).
- Regra a carregar do começo ao fim: **domínio e aplicação não conhecem SQL, HTTP
  nem HTML.**
