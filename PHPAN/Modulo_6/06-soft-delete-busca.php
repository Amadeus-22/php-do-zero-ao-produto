<?php

// PHPAN · Módulo 6 · Aula 06 — Soft delete e busca
// metadados em aulas.json · a ideia em 06-soft-delete-busca.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\CriterioDeBusca;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Support\Container;

$pdo = bancoDaAula();
$service = Container::clienteService();
$repo = Container::repositorioDeClientes();

foreach ([['Ana Souza', 'ana@exemplo.com'], ['Bruno Silva', 'bruno@exemplo.com'], ['Carla Silva', 'carla@exemplo.com']] as [$n, $e]) {
    $service->criar(['nome' => $n, 'email' => $e]);
}

titulo('Aula 6 — Soft delete e busca');

secao('DELETE físico é irreversível — e leva o histórico junto');

nota('Cliente é entidade central: o usuário VAI clicar errado em algum momento.');
nota('E o DELETE em cascata levaria atividades, anexos e auditoria com ele.');

secao('Soft delete: o registro fica, o sistema deixa de ver');

$alvo = $service->criar(['nome' => 'Excluído Teste', 'email' => 'excluido@exemplo.com']);
$id = (int) $alvo->id();
$service->remover($id);

checa('some das listagens', $service->buscarPorId($id) === null, 'buscarPorId devolve null');
checa('some da contagem', count($service->listar()) === 3, count($service->listar()) . ' visíveis');

$linha = $pdo->query("SELECT nome, deletado_em FROM clientes WHERE id = {$id}")->fetch();
checa('mas a LINHA continua no banco', $linha !== false, "nome={$linha['nome']}");
checa('com deletado_em preenchido', $linha['deletado_em'] !== null, (string) $linha['deletado_em']);

secao('Lixeira e restauração');

$lixeira = $service->lixeira();
checa('a lixeira mostra o excluído', count($lixeira) === 1 && $lixeira[0]->nome() === 'Excluído Teste', '');

$service->restaurar($id);
checa('restaurar traz de volta', $service->buscarPorId($id) !== null, '');
checa('e some da lixeira', $service->lixeira() === [], '');

$acoes = $pdo->query("SELECT acao FROM auditoria WHERE entidade_id = {$id} ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
checa('exclusão E restauração foram auditadas', in_array('cliente.restaurado', $acoes, true), implode(', ', $acoes));
nota('Restaurar é tão sensível quanto excluir: pode reviver algo que deveria');
nota('continuar fora. Mesma permissão, mesmo rastro.');

secao('ARMADILHA — UNIQUE x soft delete');

$service->remover($id);

checaExcecao(
    'e-mail do excluído ainda ocupa o UNIQUE',
    EmailJaCadastrado::class,
    static fn () => $service->criar(['nome' => 'Outro', 'email' => 'excluido@exemplo.com']),
);
nota('O registro "excluído" continua na tabela, então o UNIQUE KEY continua valendo.');
nota('Soluções: índice único parcial (onde o banco suportar) ou incluir');
nota('deletado_em na chave única. Ficou anotado como decisão pendente do projeto.');

secao('Busca respeitando o filtro');

$buscar = static fn (string $q): array => $repo->buscar(new CriterioDeBusca(q: $q, perPage: 50));

checa('busca por nome parcial', count($buscar('Silva')) === 2, 'Bruno e Carla');
checa('busca também no e-mail', count($buscar('ana@')) === 1, '');
checa('sem resultado devolve lista vazia', $buscar('zzzz') === [], 'não é erro');
checa('o excluído NÃO aparece na busca', $buscar('Excluído') === [], 'filtro central no repositório');

secao('Por que centralizar o filtro no repositório');

$fonte = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Infrastructure/Cliente/RepositorioDeClientesPdo.php');
$ocorrencias = substr_count($fonte, 'deletado_em IS NULL');

checa('o filtro aparece em todas as consultas', $ocorrencias >= 4, "{$ocorrencias} ocorrências");
nota('Qualquer SELECT novo escrito FORA do repositório volta a mostrar excluído.');
nota('É por isso que centralizar importa mais aqui do que parece.');

secao('LIKE %termo% e o limite dele');

nota('Para milhares de clientes, LIKE com índice resolve. O % no início impede');
nota('uso eficiente de índice B-tree — se a base crescer muito, FULLTEXT ou');
nota('busca dedicada. Fora de escopo agora, mas o limite existe e é conhecido.');

secao('Soft delete NÃO é backup');

nota('Protege contra engano de usuário. Não protege contra DROP TABLE, bug de');
nota('migração ou disco corrompido. Backup continua obrigatório (Módulo 7).');

fecharAula();
