<?php use App\Support\Csrf; use App\Support\View; ?>
<h1>Novo cliente</h1>

<form method="post" action="/clientes">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">

    <label>
        Nome
        <input type="text" name="nome" value="<?= View::e($antigo['nome'] ?? '') ?>">
    </label>
    <?php foreach ($erros['nome'] ?? [] as $erro): ?>
        <p class="erro"><?= View::e($erro) ?></p>
    <?php endforeach; ?>

    <label>
        E-mail
        <input type="email" name="email" value="<?= View::e($antigo['email'] ?? '') ?>">
    </label>
    <?php foreach ($erros['email'] ?? [] as $erro): ?>
        <p class="erro"><?= View::e($erro) ?></p>
    <?php endforeach; ?>

    <label>
        Telefone
        <input type="text" name="telefone" value="<?= View::e($antigo['telefone'] ?? '') ?>">
    </label>
    <?php foreach ($erros['telefone'] ?? [] as $erro): ?>
        <p class="erro"><?= View::e($erro) ?></p>
    <?php endforeach; ?>

    <button type="submit">Salvar</button>
</form>
