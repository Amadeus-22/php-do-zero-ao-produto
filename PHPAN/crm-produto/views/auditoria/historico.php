<?php use App\Support\View; ?>
<h1>Auditoria — <?= View::e($entidade) ?> #<?= (int) $entidadeId ?></h1>

<?php if ($nome !== null): ?>
    <p><?= View::e($nome) ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr><th>Quando</th><th>Ação</th><th>Quem</th><th>Antes</th><th>Depois</th></tr>
    </thead>
    <tbody>
        <?php foreach ($historico as $evento): ?>
        <tr>
            <td><?= View::e($evento['criado_em']) ?></td>
            <td><?= View::e($evento['acao']) ?></td>
            <td><?= View::e($evento['usuario_id'] ?? '(sistema)') ?></td>
            <td><?= View::e($evento['dados_antes'] ?? '—') ?></td>
            <td><?= View::e($evento['dados_depois'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($historico === []): ?>
        <tr><td colspan="5">Sem registros.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<p><a href="/clientes">Voltar</a></p>
