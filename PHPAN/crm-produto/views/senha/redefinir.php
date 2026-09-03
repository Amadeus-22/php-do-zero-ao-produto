<?php use App\Support\Csrf; use App\Support\View; ?>
<h1>Redefinir senha</h1>

<?php if (!empty($erro)): ?>
    <p class="erro"><?= View::e($erro) ?></p>
<?php endif; ?>

<form method="post" action="/redefinir-senha">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
    <label>
        Nova senha (mínimo 8 caracteres)
        <input type="password" name="senha" required autofocus>
    </label>
    <button type="submit">Redefinir</button>
</form>
