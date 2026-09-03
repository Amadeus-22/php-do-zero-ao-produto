<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

requireGuest();

if (!config('allow_registration')) {
    http_response_code(403);
    exit('Cadastro desativado. Crie usuários pelo script scripts/seed.php.');
}

$dados = ['nome' => '', 'email' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $dados['nome']  = post('nome');
    $dados['email'] = post('email');
    $senha          = post('senha');

    $erros = usuario_validar($dados['nome'], $dados['email'], $senha);

    if (!$erros) {
        usuario_criar($dados['nome'], $dados['email'], $senha);
        auth_login($dados['email'], $senha);
        flash_set('ok', 'Conta criada. Comece cadastrando seu primeiro contato.');
        redirect('/contatos/index.php');
    }
}

$titulo = 'Criar conta';
require APP_ROOT . '/templates/header.php';
?>

<div class="cartao cartao-estreito formulario">
    <h1>Criar conta</h1>
    <p class="subtitulo">Cada usuário enxerga apenas os próprios contatos.</p>

    <form method="post" action="<?= e(url('/registrar.php')) ?>" novalidate>
        <?= csrf_field() ?>

        <label for="nome">Nome <span class="obrigatorio">*</span></label>
        <input type="text" id="nome" name="nome" maxlength="120" required autofocus
               value="<?= e($dados['nome']) ?>" class="<?= isset($erros['nome']) ? 'invalido' : '' ?>">
        <?php if (isset($erros['nome'])): ?><small class="erro"><?= e($erros['nome']) ?></small><?php endif; ?>

        <label for="email">E-mail <span class="obrigatorio">*</span></label>
        <input type="email" id="email" name="email" maxlength="180" required
               value="<?= e($dados['email']) ?>" class="<?= isset($erros['email']) ? 'invalido' : '' ?>">
        <?php if (isset($erros['email'])): ?><small class="erro"><?= e($erros['email']) ?></small><?php endif; ?>

        <label for="senha">Senha <span class="obrigatorio">*</span></label>
        <input type="password" id="senha" name="senha" required
               class="<?= isset($erros['senha']) ? 'invalido' : '' ?>">
        <?php if (isset($erros['senha'])): ?><small class="erro"><?= e($erros['senha']) ?></small>
        <?php else: ?><small class="nota-curta">Mínimo de 8 caracteres.</small><?php endif; ?>

        <div class="acoes">
            <button type="submit" class="btn btn-primario">Criar conta</button>
            <a class="btn btn-secundario" href="<?= e(url('/login.php')) ?>">Voltar</a>
        </div>
    </form>
</div>

<?php require APP_ROOT . '/templates/footer.php'; ?>
