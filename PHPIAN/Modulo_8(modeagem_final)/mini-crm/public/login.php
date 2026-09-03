<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

requireGuest();

$email = '';
$erro  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = post('email');
    $senha = post('senha');

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } elseif (auth_login($email, $senha)) {
        flash_set('ok', 'Bem-vindo de volta!');
        redirect('/contatos/index.php');
    } else {
        // Mensagem genérica de propósito: não revela se o e-mail existe.
        $erro = 'E-mail ou senha inválidos.';
    }
}

$titulo = 'Entrar';
require APP_ROOT . '/templates/header.php';
?>

<div class="cartao cartao-estreito formulario">
    <h1>Entrar</h1>
    <p class="subtitulo">Acesse sua área de contatos.</p>

    <?php if ($erro): ?>
        <div class="alerta alerta-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login.php')) ?>">
        <?= csrf_field() ?>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>

        <div class="acoes">
            <button type="submit" class="btn btn-primario">Entrar</button>
        </div>
    </form>

    <?php if (config('allow_registration')): ?>
        <p class="rodape-form">
            Ainda não tem conta? <a href="<?= e(url('/registrar.php')) ?>">Cadastre-se</a>
        </p>
    <?php endif; ?>
</div>

<?php require APP_ROOT . '/templates/footer.php'; ?>
