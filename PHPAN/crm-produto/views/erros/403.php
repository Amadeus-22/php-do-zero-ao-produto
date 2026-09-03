<?php use App\Support\View; ?>
<h1>403 — acesso restrito</h1>
<p><?= View::e($mensagem ?? 'Seu papel não permite esta ação.') ?></p>
