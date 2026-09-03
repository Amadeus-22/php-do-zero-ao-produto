<?php use App\Support\Csrf; use App\Support\View; ?>
<h1>Esqueci minha senha</h1>

<?php if (!empty($mensagem)): ?>
    <p class="flash flash--sucesso"><?= View::e($mensagem) ?></p>
<?php endif; ?>

<form method="post" action="/esqueci-senha">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <label>
        E-mail
        <input type="email" name="email" required autofocus>
    </label>
    <button type="submit">Enviar link</button>
</form>

<p><a href="/login">Voltar ao login</a></p>
