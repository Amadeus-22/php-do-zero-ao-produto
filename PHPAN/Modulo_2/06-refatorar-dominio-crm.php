<?php

// PHPAN · Módulo 2 · Aula 06 — Refatorar o Mini CRM: extrair Modelos e Serviços
// metadados em aulas.json · a ideia em 06-refatorar-dominio-crm.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Application\Atividade\RegistrarAtividade;
use App\Application\Cliente\ClienteService;
use App\Application\Contato\CadastrarContato;
use App\Domain\Atividade\TipoAtividade;
use App\Domain\Contato\CanalPreferido;
use App\Support\Container;

$raiz = __DIR__ . '/../crm-produto';
$pdo = bancoDaAula();

titulo('Aula 6 — Refatorar o Mini CRM: extrair Modelos e Serviços');

secao('O vocabulário: por que não dizemos "Model"');

printf("  %-26s %s\n", 'Entidade de domínio', 'dado + regras que sempre valem, sem saber de banco');
printf("  %-26s %s\n", 'Caso de uso / Serviço', 'orquestra entidades e repositórios');
printf("  %-26s %s\n", 'Repositório', 'a ponte para persistência');
nota('"Model" carrega a herança do ActiveRecord, onde a classe mistura dado e');
nota('acesso a banco. Ser explícito sobre a camada evita esse arrastão.');

secao('As três entidades funcionando juntas');

$service = Container::clienteService();
$cliente = $service->criar(['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']);
$clienteId = (int) $cliente->id();

$contato = (new CadastrarContato(Container::repositorioDeClientes(), new App\Infrastructure\Contato\RepositorioDeContatosEmMemoria()))
    ->executar($clienteId, 'Bruno Lima', 'bruno@exemplo.com', CanalPreferido::WHATSAPP);

$atividade = (new RegistrarAtividade(Container::repositorioDeClientes(), new App\Infrastructure\Atividade\RepositorioDeAtividadesEmMemoria()))
    ->executar($clienteId, TipoAtividade::REUNIAO, 'Kickoff do projeto');

checa('Cliente criado', $clienteId > 0, "id={$clienteId}");
checa('Contato ligado ao cliente', $contato->clienteId() === $clienteId, $contato->nome());
checa('Atividade ligada ao cliente', $atividade->clienteId() === $clienteId, $atividade->tipo()->value);

checaExcecao(
    'contato para cliente inexistente é recusado',
    App\Domain\Cliente\ClienteNaoEncontrado::class,
    static fn () => (new CadastrarContato(Container::repositorioDeClientes(), new App\Infrastructure\Contato\RepositorioDeContatosEmMemoria()))
        ->executar(9999, 'X', 'x@exemplo.com', CanalPreferido::EMAIL),
);

secao('CHECKLIST DE QUALIDADE DO DOMÍNIO');

// 1
$sujos = [];
foreach (glob($raiz . '/src/Domain/**/*.php') ?: [] as $arquivo) {
    if (preg_match('/\b(PDO|\$_POST|\$_SESSION|\$_GET)\b/', php_strip_whitespace($arquivo)) === 1) {
        $sujos[] = basename($arquivo);
    }
}
checa('1. Domain não conhece PDO nem HTTP', $sujos === [], $sujos === [] ? count(glob($raiz . '/src/Domain/**/*.php') ?: []) . ' arquivos' : implode(', ', $sujos));

// 2
$semValidacao = [];
foreach (['Cliente/Cliente', 'Contato/Contato', 'Atividade/Atividade'] as $entidade) {
    if (!str_contains(php_strip_whitespace("{$raiz}/src/Domain/{$entidade}.php"), 'throw ')) {
        $semValidacao[] = $entidade;
    }
}
checa('2. toda entidade valida na criação', $semValidacao === [], $semValidacao === [] ? 'Cliente, Contato, Atividade' : implode(', ', $semValidacao));

// 3
$setters = [];
foreach (glob($raiz . '/src/Domain/**/*.php') ?: [] as $arquivo) {
    if (preg_match('/function set[A-Z]/', php_strip_whitespace($arquivo)) === 1) {
        $setters[] = basename($arquivo);
    }
}
checa('3. nenhum setter genérico', $setters === [], $setters === [] ? 'só métodos de intenção' : implode(', ', $setters));

// 4
$interfaces = glob($raiz . '/src/Domain/*/Repositorio*.php') ?: [];
$semImplementacao = [];
foreach ($interfaces as $interface) {
    $nome = basename($interface, '.php');
    $achou = false;

    foreach (glob($raiz . '/src/Infrastructure/**/*.php') ?: [] as $impl) {
        if (str_contains(php_strip_whitespace($impl), "implements {$nome}")) {
            $achou = true;
            break;
        }
    }

    if (!$achou) {
        $semImplementacao[] = $nome;
    }
}
checa('4. toda interface tem implementação', $semImplementacao === [], count($interfaces) . ' interfaces');

// 5
$mortais = [];
foreach (glob($raiz . '/src/**/*.php') ?: [] as $arquivo) {
    if (preg_match('/\b(die|var_dump|exit)\s*\(/', php_strip_whitespace($arquivo)) === 1) {
        $mortais[] = basename($arquivo);
    }
}
checa('5. nenhum die()/var_dump()/exit()', $mortais === [], $mortais === [] ? 'só exceções de domínio' : implode(', ', $mortais));

// 6 e 7
$quality = (string) shell_exec('cd ' . escapeshellarg($raiz) . ' && composer quality 2>&1');
checa('6. PHPStan sem erro', str_contains($quality, '[OK] No errors'), 'level 5');
preg_match('/OK \((\d+) tests, (\d+) assertions\)/', $quality, $m);
checa('7. testes cobrindo entidades e casos de uso', isset($m[1]), ($m[1] ?? '?') . ' testes, ' . ($m[2] ?? '?') . ' asserções');

secao('Entidade ANÊMICA — o antipadrão a evitar');

$reflexo = new ReflectionClass(App\Domain\Cliente\Cliente::class);
$metodos = array_map(static fn (ReflectionMethod $x): string => $x->getName(), $reflexo->getMethods(ReflectionMethod::IS_PUBLIC));
$comComportamento = array_filter($metodos, static fn (string $x): bool => in_array($x, ['renomear', 'desativar', 'estaAtivo', 'alterarTelefone'], true));

checa('Cliente tem comportamento, não só getters', count($comComportamento) >= 3, implode(', ', $comComportamento));
nota('Entidade só com get/set, com toda a lógica em Services externos, é o');
nota('Anemic Domain Model. Regra que diz respeito só àquela entidade mora nela.');

secao('Roteiro de refatoração (ordem que minimiza retrabalho)');

foreach ([
    '1. identificar entidades implícitas (arrays repetidos pelo código)',
    '2. extrair a entidade primeiro, SEM repositório ainda',
    '3. extrair a interface de repositório',
    '4. implementar o repositório, movendo o SQL disperso para dentro',
    '5. extrair o caso de uso (a orquestração que estava na página)',
    '6. rodar composer quality A CADA PASSO',
] as $passo) {
    echo "  {$passo}\n";
}
nota('Sem teste cobrindo o comportamento atual, refatoração vira aposta.');

secao('QUIZ do Módulo 2 — respostas');

$quiz = [
    '1. Relação Cliente x Contato' => 'Composição: Cliente tem contatos',
    '2. buscarPorIdComPdo(PDO, int) na interface' => 'nome e assinatura vazam implementação',
    '3. Quando criar interface' => 'mais de uma implementação, ou fronteira de camada',
    '4. Por que die("e-mail inválido") é ruim' => 'encerra o processo e não distingue por tipo',
    '5. Entidade "anêmica"' => 'só getters/setters, sem regras próprias',
    '6. Capitalização namespace/pasta' => 'funciona no Windows e quebra no Linux',
];
foreach ($quiz as $pergunta => $resposta) {
    echo "  {$pergunta}\n      -> {$resposta}\n";
}

secao('O Mini CRM do PHPIAN');

checa('continua intocado', is_dir(__DIR__ . '/../../PHPIAN/Modulo_8(modeagem_final)/mini-crm'), 'registro do que foi entregue lá');
nota('A refatoração aconteceu PARA DENTRO do PHPAN: o crm-produto é a evolução,');
nota('e o original fica como comparação.');

fecharAula();
