<?php use App\Support\View; ?>
<h1>404 — não encontrado</h1>
<p><?= View::e($mensagem ?? 'A página que você procurou não existe.') ?></p>
<p><a href="/clientes">Voltar para clientes</a></p>
