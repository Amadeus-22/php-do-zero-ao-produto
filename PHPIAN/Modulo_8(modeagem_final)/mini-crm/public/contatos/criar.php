<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

requireAuth();

$dados = ['nome' => '', 'email' => '', 'telefone' => '', 'notas' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    [$erros, $dados] = contato_validar($_POST);

    if (!$erros) {
        contato_criar(auth_id(), $dados);
        flash_set('ok', 'Contato "' . $dados['nome'] . '" cadastrado.');
        redirect('/contatos/index.php');   // POST -> Redirect -> GET: F5 não duplica
    }
}

$titulo      = 'Novo contato';
$acao        = url('/contatos/criar.php');
$rotuloBotao = 'Salvar contato';

require APP_ROOT . '/templates/header.php';
?>

<div class="cabecalho-pagina">
    <div>
        <h1>Novo contato</h1>
        <p class="subtitulo">Só o nome é obrigatório.</p>
    </div>
</div>

<?php
require APP_ROOT . '/templates/form_contato.php';
require APP_ROOT . '/templates/footer.php';
