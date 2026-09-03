<?php use App\Support\Csrf; use App\Support\View; ?>
<h1>Meus lembretes</h1>

<table>
    <thead>
        <tr><th>Vence em</th><th>Mensagem</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($lembretes as $lembrete): ?>
        <tr>
            <td><?= View::e($lembrete['vence_em_local']) ?></td>
            <td><?= View::e($lembrete['mensagem']) ?></td>
            <td><?= View::e($lembrete['status']) ?></td>
            <td>
                <form method="post" action="/lembretes/<?= (int) $lembrete['id'] ?>/concluir">
                    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
                    <button type="submit">Concluir</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($lembretes === []): ?>
        <tr><td colspan="4">Nenhum lembrete pendente.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<p class="muted">As datas são gravadas em UTC e exibidas no seu fuso.</p>
<p><a href="/clientes">Voltar</a></p>
