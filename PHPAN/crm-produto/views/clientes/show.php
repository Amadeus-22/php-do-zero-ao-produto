<?php use App\Support\Csrf; use App\Support\View; ?>
<h1><?= View::e($cliente->nome()) ?></h1>
<dl>
    <dt>E-mail</dt><dd><?= View::e($cliente->email()) ?></dd>
    <dt>Telefone</dt><dd><?= View::e($cliente->telefone() ?? '—') ?></dd>
    <dt>Status</dt><dd><?= $cliente->estaAtivo() ? 'ativo' : 'inativo' ?></dd>
    <dt>Criado em</dt><dd><?= View::e($cliente->criadoEm()->format('d/m/Y H:i')) ?></dd>
</dl>

<form method="post" action="/clientes/<?= (int) $cliente->id() ?>/remover">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <button type="submit">Remover</button>
</form>

<h2>Lembrete de follow-up</h2>

<form method="post" action="/clientes/<?= (int) $cliente->id() ?>/lembretes">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <label>
        Mensagem
        <input type="text" name="mensagem" placeholder="Ligar para fechar a proposta" required>
    </label>
    <label>
        Quando
        <input type="datetime-local" name="vence_em" required>
    </label>
    <button type="submit">Criar lembrete</button>
</form>

<h2>Anexos</h2>

<form method="post" action="/clientes/<?= (int) $cliente->id() ?>/anexos" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <input type="file" name="arquivo" accept=".pdf,.jpg,.jpeg,.png" required>
    <button type="submit">Anexar</button>
</form>

<ul>
    <?php foreach ($anexos ?? [] as $anexo): ?>
        <li>
            <a href="/anexos/<?= (int) $anexo['id'] ?>"><?= View::e($anexo['nome_original']) ?></a>
            <span class="muted">(<?= View::e($anexo['mime_real']) ?>, <?= (int) round($anexo['tamanho_bytes'] / 1024) ?> KB)</span>
        </li>
    <?php endforeach; ?>
    <?php if (($anexos ?? []) === []): ?>
        <li>Nenhum anexo.</li>
    <?php endif; ?>
</ul>

<p>
    <a href="/auditoria/cliente/<?= (int) $cliente->id() ?>">Ver histórico (auditoria)</a>
</p>

<a href="/clientes">Voltar</a>
