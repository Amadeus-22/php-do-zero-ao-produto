<?php

// PHPAN · Módulo 1 · Aula 03 — Como um produto PHP "de verdade" se parece
// metadados em aulas.json · a ideia em 03-produto-em-camadas.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Application\Cliente\CadastrarCliente;
use App\Application\Contato\CadastrarContato;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Contato\CanalPreferido;
use App\Infrastructure\Cliente\RepositorioDeClientesEmMemoria;
use App\Infrastructure\Contato\RepositorioDeContatosEmMemoria;

titulo('Aula 3 — Como um produto PHP de verdade se parece');

secao('Infraestrutura: as implementações concretas');

$clientes = new RepositorioDeClientesEmMemoria();
$contatos = new RepositorioDeContatosEmMemoria();

checa(
    'RepositorioDeClientesEmMemoria cumpre o contrato do domínio',
    $clientes instanceof RepositorioDeClientes,
    'implements RepositorioDeClientes',
);
nota('A seta aponta para DENTRO: a infra implementa o que o domínio declarou.');

secao('Aplicação: o caso de uso orquestra, sem saber quem chamou');

$cadastrarCliente = new CadastrarCliente($clientes);
$cadastrarContato = new CadastrarContato($clientes, $contatos);

$cliente = $cadastrarCliente->executar('Ana Souza', 'ana@exemplo.com');
$clienteId = $cliente->id() ?? 0;
checa('CadastrarCliente devolve o cliente persistido', $clienteId === 1, "id={$clienteId}");

checaExcecao(
    'CadastrarContato recusa cliente inexistente',
    ClienteNaoEncontrado::class,
    static fn () => $cadastrarContato->executar(99, 'Bruno', 'bruno@exemplo.com', CanalPreferido::EMAIL),
);
nota('Essa regra vale venha a chamada de formulário, API ou script de importação.');

secao('Apresentação: só traduz entrada e saída');

// Simula o que chega da web. A camada de apresentação NÃO tem regra de negócio.
$post = ['cliente_id' => (string) $clienteId, 'nome' => 'Bruno Lima', 'email' => 'bruno@exemplo.com', 'canal_preferido' => 'whatsapp'];

$canal = CanalPreferido::from($post['canal_preferido']);
$status = 0;
$corpo = '';

try {
    $contato = $cadastrarContato->executar(
        clienteId: (int) $post['cliente_id'],
        nome: $post['nome'],
        email: $post['email'],
        canal: $canal,
    );
    $status = 302;
    $corpo = '/contatos/' . $contato->id();
} catch (\DomainException $e) {
    $status = 422;
    $corpo = htmlspecialchars($e->getMessage());
}

checa('caminho feliz vira redirect', $status === 302, "HTTP {$status} -> {$corpo}");

// Mesma apresentação, entrada ruim: muda só o status e a mensagem.
try {
    $cadastrarContato->executar(999, 'X', 'x@exemplo.com', CanalPreferido::EMAIL);
    $status = 302;
} catch (\DomainException $e) {
    $status = 422;
    $corpo = $e->getMessage();
}
checa('erro de domínio vira 422, não página branca', $status === 422, $corpo);

secao('Exercício da aula: de que camada é cada linha?');

$exercicio = [
    'session_start() e uso de $_POST'          => 'Apresentação (ponta HTTP)',
    '$_POST["nome"] === "" (regra "obrigatório")' => 'Aplicação/Domínio — hoje está misturado na apresentação',
    'new PDO(...) e o INSERT'                  => 'Infraestrutura, atrás de RepositorioDeClientes',
    'echo "<h1>Cliente cadastrado!</h1>"'      => 'Apresentação',
];
foreach ($exercicio as $linha => $camada) {
    printf("  %-44s -> %s\n", $linha, $camada);
}
nota('O objeto Cliente seria o Domínio; CadastrarCliente, a Aplicação que costura tudo.');

secao('Verificação estrutural do projeto');

foreach (['Domain', 'Application'] as $camada) {
    $arquivos = glob(__DIR__ . "/../crm-produto/src/{$camada}/*/*.php") ?: [];
    $sujos = array_filter(
        $arquivos,
        static fn (string $f): bool => preg_match('/\b(PDO|\$_POST|\$_SESSION|\$_GET)\b/', php_strip_whitespace($f)) === 1,
    );
    checa("src/{$camada}/ não conhece PDO nem HTTP", $sujos === [], count($arquivos) . ' arquivos verificados');
}

fecharAula();
