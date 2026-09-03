<?php use App\Support\View; ?>
<h1>Clientes</h1>
<a href="/clientes/novo" class="btn">Novo cliente</a>

<table>
    <thead>
        <tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($clientes as $cliente): ?>
        <tr>
            <td><?= View::e($cliente->nome()) ?></td>
            <td><?= View::e($cliente->email()) ?></td>
            <td><?= View::e($cliente->telefone() ?? '—') ?></td>
            <td><a href="/clientes/<?= (int) $cliente->id() ?>">ver</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($clientes === []): ?>
        <tr><td colspan="4">Nenhum cliente ainda.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
