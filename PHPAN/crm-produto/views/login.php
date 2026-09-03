<?php use App\Support\Csrf; use App\Support\View; ?>
<h1>Entrar</h1>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= View::e($erro) ?></p>
<?php endif; ?>

<form method="post" action="/login">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">

    <label>
        E-mail
        <input type="email" name="email" value="<?= View::e($antigo['email'] ?? '') ?>" autofocus>
    </label>

    <label>
        Senha
        <input type="password" name="senha">
    </label>

    <button type="submit">Entrar</button>
</form>
