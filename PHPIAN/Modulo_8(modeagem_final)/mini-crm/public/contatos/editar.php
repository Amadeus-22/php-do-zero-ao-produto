<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

requireAuth();

$id = (int) ($_REQUEST['id'] ?? 0);

// Ownership: contato_buscar filtra por user_id — contato de outro usuário
// responde exatamente como um id inexistente.
$contato = contato_buscar($id, auth_id())
    ?? abortar(404, 'Contato não encontrado.');

$dados = [
    'nome'     => (string) $contato['nome'],
    'email'    => (string) ($contato['email'] ?? ''),
    'telefone' => (string) ($contato['telefone'] ?? ''),
    'notas'    => (string) ($contato['notas'] ?? ''),
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    [$erros, $dados] = contato_validar($_POST);

    if (!$erros) {
        contato_atualizar($id, auth_id(), $dados);   // o WHERE repete o user_id
        flash_set('ok', 'Contato atualizado.');
        redirect('/contatos/index.php');
    }
}

$titulo      = 'Editar contato';
$acao        = url('/contatos/editar.php?id=' . $id);
$rotuloBotao = 'Salvar alterações';

require APP_ROOT . '/templates/header.php';
?>

<div class="cabecalho-pagina">
    <div>
        <h1>Editar contato</h1>
        <p class="subtitulo">Criado em <?= e(data_br($contato['criado_em'])) ?>.</p>
    </div>
    <a class="btn btn-secundario" href="<?= e(url('/contatos/excluir.php?id=' . $id)) ?>">Excluir</a>
</div>

<?php
require APP_ROOT . '/templates/form_contato.php';
require APP_ROOT . '/templates/footer.php';
