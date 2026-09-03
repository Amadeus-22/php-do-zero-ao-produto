# Aula 03 — Como um produto PHP "de verdade" se parece

**Curso:** PHPAN (intermediário) · **Módulo 1** — Mapa do intermediário e revisão ativa
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/03-produto-em-camadas

Status: [x] assistida · [x] anotada · [ ] praticada

## Objetivo

Olhar para qualquer trecho de código e dizer "isso é domínio, isso é aplicação,
isso é infraestrutura, isso é apresentação" — e explicar por que a separação importa.

## Conceito

"Camadas" não é jargão vazio. É resposta a um problema concreto: **quando tudo está
misturado, uma mudança pequena quebra coisas que pareciam não ter relação.** Se a
regra "cliente precisa ter e-mail válido" está espalhada em 5 arquivos (formulário,
controller, API, importador CSV, seed de teste), corrigi-la significa caçar 5 lugares
— e esquecer um deles é como o bug nasce.

A solução é dar **um único lugar** para cada tipo de responsabilidade:

```
Apresentação (Web/API)
        ↓ chama
Aplicação (Casos de uso / Services)
        ↓ usa
Domínio (Entidades, Regras de negócio, Interfaces)
        ↑ implementado por
Infraestrutura (PDO, arquivos, e-mail, fila)
```

Repare no sentido da última seta: a infraestrutura **implementa** interfaces que o
domínio declara. A dependência aponta para dentro, nunca do domínio para fora.

### 1. Domínio

O coração. `Cliente`, `Contato`, `Atividade` e as regras que **sempre** valem, não
importa de onde vem a chamada (web, API, script de importação). Não sabe que existe
banco de dados, HTTP ou HTML.

```php
<?php

declare(strict_types=1);

namespace App\Domain\Contato;

enum CanalPreferido: string
{
    case EMAIL = 'email';
    case TELEFONE = 'telefone';
    case WHATSAPP = 'whatsapp';
}

final class Contato
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private string $nome,
        private string $email,
        private CanalPreferido $canalPreferido,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function canalPreferido(): CanalPreferido
    {
        return $this->canalPreferido;
    }
}
```

Nenhuma menção a SQL, `$_POST` ou JSON. O arquivo funcionaria igual trocando MySQL
por SQLite, ou se o dado chegasse de uma planilha importada.

### 2. Aplicação (casos de uso)

Orquestra o domínio para realizar uma ação concreta: "cadastrar contato", "mudar
canal preferido", "listar contatos de um cliente". Validações de fluxo e coordenação
entre repositórios moram aqui — mas ainda **sem** saber se quem chamou foi um
formulário web ou uma rota de API.

```php
<?php

declare(strict_types=1);

namespace App\Application\Contato;

use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Contato\CanalPreferido;
use App\Domain\Contato\Contato;
use App\Domain\Contato\RepositorioDeContatos;

final readonly class CadastrarContato
{
    public function __construct(
        private RepositorioDeClientes $clientes,
        private RepositorioDeContatos $contatos,
    ) {
    }

    public function executar(
        int $clienteId,
        string $nome,
        string $email,
        CanalPreferido $canal,
    ): Contato {
        if ($this->clientes->buscarPorId($clienteId) === null) {
            throw new \DomainException("Cliente {$clienteId} não existe.");
        }

        $contato = new Contato(
            id: null,
            clienteId: $clienteId,
            nome: $nome,
            email: $email,
            canalPreferido: $canal,
        );

        return $this->contatos->salvar($contato);
    }
}
```

### 3. Infraestrutura

Implementa os detalhes técnicos que o domínio pede via interface: como salvar no
MySQL, como enviar e-mail de verdade, como gravar arquivo. É a camada que **muda**
quando você troca de banco, provedor de e-mail ou storage — sem o domínio saber.

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Contato;

use App\Domain\Contato\Contato;
use App\Domain\Contato\RepositorioDeContatos;
use PDO;

final readonly class RepositorioDeContatosPdo implements RepositorioDeContatos
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function salvar(Contato $contato): Contato
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contatos (cliente_id, nome, email, canal_preferido)
             VALUES (:cliente_id, :nome, :email, :canal)'
        );
        $stmt->execute([
            'cliente_id' => $contato->clienteId(),
            'nome' => $contato->nome(),
            'email' => $contato->email(),
            'canal' => $contato->canalPreferido()->value,
        ]);

        return new Contato(
            id: (int) $this->pdo->lastInsertId(),
            clienteId: $contato->clienteId(),
            nome: $contato->nome(),
            email: $contato->email(),
            canalPreferido: $contato->canalPreferido(),
        );
    }
}
```

### 4. Apresentação (Web/API)

A ponta que fala com o mundo: recebe `$_POST` ou JSON, chama o caso de uso certo,
devolve HTML ou JSON. **Fina de propósito** — sem regra de negócio, só tradução de
entrada/saída.

```php
<?php

declare(strict_types=1);

// Trecho de um controller (formalizado no Módulo 3)
$canal = App\Domain\Contato\CanalPreferido::from($_POST['canal_preferido'] ?? 'email');

try {
    $contato = $cadastrarContato->executar(
        clienteId: (int) $_POST['cliente_id'],
        nome: $_POST['nome'],
        email: $_POST['email'],
        canal: $canal,
    );

    header('Location: /contatos/' . $contato->id());
} catch (\DomainException $e) {
    http_response_code(422);
    echo "Erro: " . htmlspecialchars($e->getMessage());
}
```

### Por que compensa o "trabalho extra"

São mais arquivos que uma página só. O ganho aparece quando o produto cresce:

- **Expor a mesma ação via API além do painel?** Reaproveita `CadastrarContato`
  inteiro, só troca a apresentação.
- **Trocar MySQL por outro banco?** Só reescreve a infraestrutura; o domínio não muda.
- **Testar a regra "cliente precisa existir antes do contato"?** Testa
  `CadastrarContato` isolado, sem banco de verdade.

## Na prática

Exercício de leitura de arquitetura — identificar a camada de cada linha:

```php
<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=crm', 'root', '');

if ($_POST['nome'] === '') {
    echo "Nome obrigatório";
    exit;
}

$stmt = $pdo->prepare('INSERT INTO clientes (nome, email) VALUES (?, ?)');
$stmt->execute([$_POST['nome'], $_POST['email']]);

echo "<h1>Cliente cadastrado!</h1>";
```

| Trecho | Camada |
|---|---|
| `session_start()` e uso de `$_POST` | **Apresentação** (é a ponta HTTP) |
| `$_POST['nome'] === ''` | Hoje mistura apresentação com regra. "Nome obrigatório" pertence à **Aplicação** (ou ao domínio, como invariante do construtor de `Cliente`); a apresentação só decide *como mostrar* o erro |
| Conexão PDO + `INSERT` | **Infraestrutura**, escondida atrás de um `RepositorioDeClientes` |
| `echo "<h1>..."` | **Apresentação** |

O objeto `Cliente` (com nome e e-mail validados) seria o **Domínio**, e uma classe
`CadastrarCliente` seria a **Aplicação** que costura tudo.

## Pontos de atenção

- **Toda camada com pasta própria desde o dia 1 de um protótipo?** Para rascunho
  descartável, tudo bem simplificar. Para o CRM de produto do curso, não — é
  exatamente o exercício de organizar desde cedo.
- **Confundir "camada de aplicação" com "controller"** — controller é apresentação.
  O caso de uso não sabe se foi chamado por um controller web ou um comando de
  terminal.
- **Vazar infraestrutura para o domínio** — sinal clássico: classe de domínio que
  importa `PDO` ou monta SQL. Se acontece, a camada está furada.
- **Over-engineering em projeto pequeno** — nem tudo precisa de interface +
  implementação separada se é minúsculo e descartável. Mas o CRM de produto **não é**
  descartável.

## Entrega da aula

Diagrama simples (pode ser ASCII, como o desta aula) com as 4 camadas e, para cada
uma, 2-3 classes que o **meu** CRM vai ter (base do briefing: Cliente, Contato,
Atividade). Guardar para comparar com a estrutura real construída a partir do Módulo 2.

```
Apresentação   →
Aplicação      →
Domínio        →
Infraestrutura →
```

## Aplicar no CRM de produto

Estrutura de pastas que essas 4 camadas viram em `src/`:

```
src/
  Domain/         ← entidades, enums, interfaces de repositório
  Application/    ← casos de uso (CadastrarContato, ListarClientes…)
  Infrastructure/ ← implementações PDO, e-mail, arquivo
  Http/           ← controllers, rotas (Módulo 3)
```
