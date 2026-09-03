<?php

// PHPAN · Módulo 5 · Aula 06 — Auditoria: quem fez o quê
// metadados em aulas.json · a ideia em 06-auditoria.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Auditoria\AuditLogger;
use App\Application\Cliente\ClienteService;
use App\Domain\Usuario\Gate;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Infrastructure\Cliente\RepositorioDeClientesPdo;
use App\Support\Container;

$pdo = bancoDaAula();
$auditoria = new AuditLogger($pdo);
$service = new ClienteService(new RepositorioDeClientesPdo($pdo), $auditoria);

$admin = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Admin', 'admin@exemplo.com', 'senha-de-estudo', Papel::ADMIN),
);
$autorId = (int) $admin->id();

titulo('Aula 6 — Auditoria: quem fez o quê');

secao('Auditoria NÃO é log de debug');

printf("  %-14s %-28s %s\n", '', 'LOG DE APLICAÇÃO', 'AUDITORIA');
printf("  %-14s %-28s %s\n", 'Público', 'devs', 'admin / negócio / compliance');
printf("  %-14s %-28s %s\n", 'Conteúdo', 'erro, stack trace', 'ação de negócio (quem, o quê, quando)');
printf("  %-14s %-28s %s\n", 'Mutabilidade', 'pode rotacionar/apagar', 'append-only, não se apaga');
printf("  %-14s %-28s %s\n", 'Retenção', 'dias/semanas', 'meses/anos');

secao('O rastro das três ações sensíveis');

$cliente = $service->criar(['nome' => 'Bruno Lima', 'email' => 'bruno@exemplo.com'], $autorId);
$clienteId = (int) $cliente->id();

$service->atualizar($clienteId, ['nome' => 'Bruno L. Lima', 'email' => 'bruno@exemplo.com'], $autorId);
$service->remover($clienteId, $autorId);

$registros = $pdo->query('SELECT * FROM auditoria ORDER BY id')->fetchAll();

checa('criar, editar e excluir deixaram rastro', count($registros) === 3, count($registros) . ' registros');
checa(
    'na ordem em que aconteceram',
    array_column($registros, 'acao') === ['cliente.criado', 'cliente.editado', 'cliente.excluido'],
    implode(' -> ', array_column($registros, 'acao')),
);

secao('Cada linha responde: quem, o quê, quando, e o que mudou');

$edicao = $registros[1];
$antes = json_decode((string) $edicao['dados_antes'], true);
$depois = json_decode((string) $edicao['dados_depois'], true);

checa('QUEM: usuario_id do autor', (int) $edicao['usuario_id'] === $autorId, "usuario_id={$autorId}");
checa('O QUÊ: entidade e id', $edicao['entidade'] === 'cliente' && (int) $edicao['entidade_id'] === $clienteId, '');
checa('QUANDO: criado_em preenchido', !empty($edicao['criado_em']), (string) $edicao['criado_em']);
checa('MUDOU DE: ' . $antes['nome'], $antes['nome'] === 'Bruno Lima', '');
checa('MUDOU PARA: ' . $depois['nome'], $depois['nome'] === 'Bruno L. Lima', '');

secao('A exclusão é a MAIS esquecida — e a mais sensível');

$exclusao = $registros[2];
checa('exclusão foi auditada', $exclusao['acao'] === 'cliente.excluido', '');
checa('com o estado anterior guardado', $exclusao['dados_antes'] !== null, 'sem isso o rastro não serve de prova');
nota('Passa despercebida porque parece "só um UPDATE deletado_em".');

secao('Campo sensível NUNCA entra no rastro');

$auditoria->registrar(
    $autorId,
    'usuario.senha_trocada',
    'usuario',
    $autorId,
    dadosAntes: ['email' => 'admin@exemplo.com', 'senha_hash' => '$2y$naoDeveriaAparecer', 'token' => 'abc123'],
);

$ultimo = $pdo->query('SELECT dados_antes FROM auditoria ORDER BY id DESC LIMIT 1')->fetchColumn();
$dados = json_decode((string) $ultimo, true);

checa('o e-mail ficou', array_key_exists('email', $dados), '');
checa('senha_hash foi removido', !array_key_exists('senha_hash', $dados), 'filtro de campo sensível');
checa('token foi removido', !array_key_exists('token', $dados), '');

secao('APPEND-ONLY: a propriedade que dá valor de prova');

$sql = shell_exec('grep -rilE "(UPDATE|DELETE FROM) *auditoria" ' . escapeshellarg(__DIR__ . '/../crm-produto/src') . ' 2>/dev/null');
checa('nenhum UPDATE/DELETE em auditoria no código', trim((string) $sql) === '', 'só INSERT');
nota('Se quem errou pode apagar o próprio rastro, a auditoria deixa de ser prova.');

secao('Ação do SISTEMA grava usuário nulo');

$semAutor = $service->criar(['nome' => 'Importado', 'email' => 'importado@exemplo.com']);
$linha = $pdo->query('SELECT usuario_id FROM auditoria ORDER BY id DESC LIMIT 1')->fetch();

checa('job/cron grava usuario_id NULL', $linha['usuario_id'] === null, 'não é erro: é ação sem usuário logado');

secao('Consulta restrita a admin');

$gate = new Gate();
checa('admin vê auditoria', $gate->pode(Papel::ADMIN, 'auditoria.ver'), '');
checa('vendedor não vê', !$gate->pode(Papel::VENDEDOR, 'auditoria.ver'), '');
checa('leitura não vê', !$gate->pode(Papel::LEITURA, 'auditoria.ver'), '');

$historico = $auditoria->historicoDe('cliente', $clienteId);
checa('histórico da entidade, do mais novo ao mais antigo', $historico[0]['acao'] === 'cliente.excluido', count($historico) . ' eventos');

fecharAula();
