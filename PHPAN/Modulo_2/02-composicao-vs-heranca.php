<?php

// PHPAN · Módulo 2 · Aula 02 — Encapsulamento, composição vs herança
// metadados em aulas.json · a ideia em 02-composicao-vs-heranca.md

declare(strict_types=1);

namespace Aula\M02A02 {
    // Classes de exemplo da aula, em namespace próprio para não colidir com o
    // domínio do CRM. As do projeto ficam em App\Domain.

    final class ContaSemEncapsulamento
    {
        public float $saldo = 0.0;
    }

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

    // "É UM": Administrador É UM Usuario — especialização estável.
    abstract class Usuario
    {
        public function __construct(
            protected readonly int $id,
            protected readonly string $nome,
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

    // "TEM UM": Cliente TEM UM Endereco — posse, não especialização.
    final class Endereco
    {
        public function __construct(
            private readonly string $logradouro,
            private readonly string $cidade,
            private readonly string $uf,
        ) {
        }

        public function formatado(): string
        {
            return "{$this->logradouro}, {$this->cidade}/{$this->uf}";
        }
    }

    final class ClienteComEndereco
    {
        private ?Endereco $endereco = null;

        public function __construct(
            private readonly string $nome,
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

    /** Colaboradora extraída — o jeito CERTO de reaproveitar código. */
    final class ConexaoFalsa
    {
        /** @var list<string> */
        public array $executadas = [];

        public function executar(string $sql): void
        {
            $this->executadas[] = $sql;
        }
    }

    final class RepositorioQueUsaConexao
    {
        public function __construct(
            private readonly ConexaoFalsa $conexao,
        ) {
        }

        public function salvar(): void
        {
            $this->conexao->executar('INSERT INTO clientes ...');
        }
    }
}

namespace {
    require __DIR__ . '/../_aula.php';
    require __DIR__ . '/../crm-produto/vendor/autoload.php';

    use Aula\M02A02\Administrador;
    use Aula\M02A02\ClienteComEndereco;
    use Aula\M02A02\ConexaoFalsa;
    use Aula\M02A02\ContaComSaldo;
    use Aula\M02A02\ContaSemEncapsulamento;
    use Aula\M02A02\Endereco;
    use Aula\M02A02\RepositorioQueUsaConexao;
    use Aula\M02A02\Usuario;
    use Aula\M02A02\Vendedor;
    use App\Domain\Contato\CanalPreferido;
    use App\Domain\Contato\Contato;
    use App\Domain\Usuario\Papel;

    titulo('Aula 2 — Encapsulamento, composição vs herança');

    secao('Encapsulamento: impedir estado inválido, não "esconder por esconder"');

    $aberta = new ContaSemEncapsulamento();
    $aberta->saldo = -500.0;
    checa('sem encapsulamento, saldo fica negativo', $aberta->saldo === -500.0, 'nada impediu');

    $protegida = new ContaComSaldo();
    $protegida->depositar(100.0);
    checa('com encapsulamento, depósito válido entra', $protegida->saldo() === 100.0, '');
    checaExcecao('e depósito negativo é recusado', InvalidArgumentException::class, static fn () => $protegida->depositar(-500.0));

    secao('HERANÇA = "é um" (especialização)');

    $admin = new Administrador(1, 'Ana');
    $vendedor = new Vendedor(2, 'Bruno');

    checa('Administrador É UM Usuario', $admin instanceof Usuario, 'serve onde se espera Usuario');
    checa('e o comportamento especializa', $admin->podeGerenciarUsuarios() && !$vendedor->podeGerenciarUsuarios(), '');

    $props = (new ReflectionClass(Usuario::class))->getProperties();
    $protegidas = array_filter($props, static fn (ReflectionProperty $p): bool => $p->isProtected());
    checa('a base usa protected, não private', count($protegidas) === count($props), 'subclasse acessa; código externo não');
    nota('Esse é o único uso legítimo de protected no dia a dia.');

    secao('COMPOSIÇÃO = "tem um" (posse)');

    $cliente = new ClienteComEndereco('Ana Souza');
    $cliente->definirEndereco(new Endereco('Rua A, 100', 'São Paulo', 'SP'));

    checa('Cliente TEM UM Endereco', $cliente->endereco() instanceof Endereco, $cliente->endereco()?->formatado() ?? '');
    checa('e NÃO é um Endereco', !($cliente instanceof Endereco), 'não herda dele');

    secao('O ANTIPADRÃO: herança para reaproveitar código');

    nota('class RepositorioDeClientes extends RepositorioBase — "funciona", mas');
    nota('RepositorioDeClientes NÃO É UM tipo de RepositorioBase: ele só queria');
    nota('reusar o método executar(). Isso é reuso disfarçado de herança.');

    $conexao = new ConexaoFalsa();
    (new RepositorioQueUsaConexao($conexao))->salvar();

    checa('com composição o repositório USA a conexão', count($conexao->executadas) === 1, 'não herda dela');
    nota('Quer logar toda query amanhã? Muda só ConexaoSql — nenhum repositório,');
    nota('e sem risco de quebrar uma cadeia de herança.');

    secao('O exercício da aula, resolvido');

    $decisoes = [
        'Contato e Cliente' => 'COMPOSIÇÃO — Cliente tem contatos',
        'Administrador e Usuario' => 'HERANÇA (ou enum) — especialização estável',
        'Atividade e TipoAtividade' => 'COMPOSIÇÃO — e o tipo é melhor como enum',
        'RelatorioEmPdf e RelatorioEmCsv' => 'INTERFACE — nem uma nem outra (aula 3)',
    ];
    foreach ($decisoes as $par => $resposta) {
        printf("  %-34s %s\n", $par, $resposta);
    }

    secao('O que o CRM escolheu');

    $contato = new Contato(null, 1, 'Bruno', 'bruno@exemplo.com', CanalPreferido::EMAIL);
    checa('Contato TEM UM CanalPreferido (enum)', $contato->canalPreferido() instanceof CanalPreferido, 'composição');

    $temHeranca = [];
    foreach (glob(__DIR__ . '/../crm-produto/src/Domain/*/*.php') ?: [] as $arquivo) {
        if (preg_match('/^(final )?class \w+ extends (?!\\\\?DomainException)/m', php_strip_whitespace($arquivo)) === 1) {
            $temHeranca[] = basename($arquivo);
        }
    }
    checa('nenhuma herança no domínio (fora de exceção)', $temHeranca === [], $temHeranca === [] ? 'só composição' : implode(', ', $temHeranca));

    checa('papéis viraram enum, não árvore de classes', count(Papel::cases()) === 3, 'admin, vendedor, leitura');
    nota('Papel é dado + comportamento simples: dois predicados. Uma hierarquia');
    nota('de classes aqui seria peso sem ganho.');

    secao('Quando herança AINDA é certa');

    foreach ([
        'relação "é um" genuína e ESTÁVEL (não muda no próximo requisito)',
        'subclasses compartilham COMPORTAMENTO, não só dados',
        'variação FECHADA de tipos',
    ] as $criterio) {
        echo "  · {$criterio}\n";
    }
    nota('Hierarquia de 3+ níveis quase sempre achata com composição + interfaces.');

    fecharAula();
}
