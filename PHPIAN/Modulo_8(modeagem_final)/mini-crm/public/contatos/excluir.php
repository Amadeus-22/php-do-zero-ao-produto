<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

requireAuth();

$id = (int) ($_REQUEST['id'] ?? 0);

$contato = contato_buscar($id, auth_id())
    ?? abortar(404, 'Contato não encontrado.');

// GET apenas confirma; quem apaga é o POST com CSRF.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    contato_excluir($id, auth_id());
    flash_set('ok', 'Contato "' . $contato['nome'] . '" excluído.');
    redirect('/contatos/index.php');
}

$titulo = 'Excluir contato';
require APP_ROOT . '/templates/header.php';
?>

<div class="cartao cartao-estreito">
    <h1>Excluir contato</h1>
    <p class="subtitulo">Esta ação não pode ser desfeita.</p>

    <p>
        Excluir <strong><?= e($contato['nome']) ?></strong><?php
            if ($contato['email']) { echo ' (' . e($contato['email']) . ')'; }
        ?>?
    </p>

    <form method="post" action="<?= e(url('/contatos/excluir.php?id=' . $id)) ?>">
        <?= csrf_field() ?>
        <div class="acoes">
            <button type="submit" class="btn btn-perigo">Sim, excluir</button>
            <a class="btn btn-secundario" href="<?= e(url('/contatos/index.php')) ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php require APP_ROOT . '/templates/footer.php'; ?>
