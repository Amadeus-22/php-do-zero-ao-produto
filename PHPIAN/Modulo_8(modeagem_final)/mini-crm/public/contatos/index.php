<?php
declare(strict_types=1);
require __DIR__ . '/../../src/bootstrap.php';

requireAuth();

$busca    = query('q');
$contatos = contatos_listar(auth_id(), $busca);

$titulo = 'Contatos';
require APP_ROOT . '/templates/header.php';
?>

<div class="cabecalho-pagina">
    <div>
        <h1>Contatos</h1>
        <p class="subtitulo">
            <?= count($contatos) ?> <?= count($contatos) === 1 ? 'contato' : 'contatos' ?>
            <?= $busca !== '' ? 'para "' . e($busca) . '"' : 'na sua agenda' ?>
        </p>
    </div>
    <a class="btn btn-primario" href="<?= e(url('/contatos/criar.php')) ?>">Novo contato</a>
</div>

<form class="busca" method="get" action="<?= e(url('/contatos/index.php')) ?>">
    <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou e-mail...">
    <button type="submit" class="btn btn-secundario">Buscar</button>
    <?php if ($busca !== ''): ?>
        <a class="btn btn-secundario" href="<?= e(url('/contatos/index.php')) ?>">Limpar</a>
    <?php endif; ?>
</form>

<?php if (!$contatos): ?>
    <div class="cartao vazio">
        <?php if ($busca !== ''): ?>
            Nenhum contato encontrado para <strong><?= e($busca) ?></strong>.
        <?php else: ?>
            Sua agenda está vazia. <a href="<?= e(url('/contatos/criar.php')) ?>">Cadastre o primeiro contato</a>.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="tabela-envolucro">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contatos as $contato): ?>
                <tr>
                    <td>
                        <?= e($contato['nome']) ?>
                        <?php if ($contato['notas'] !== null && $contato['notas'] !== ''): ?>
                            <span class="nota-curta"><?= e(mb_strimwidth($contato['notas'], 0, 60, '…')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($contato['email'] ?? '—') ?></td>
                    <td><?= e($contato['telefone'] ?? '—') ?></td>
                    <td><?= e(data_br($contato['criado_em'])) ?></td>
                    <td class="acoes-linha">
                        <a href="<?= e(url('/contatos/editar.php?id=' . $contato['id'])) ?>">Editar</a>
                        <a class="perigo" href="<?= e(url('/contatos/excluir.php?id=' . $contato['id'])) ?>">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/templates/footer.php'; ?>
