<?php
/**
 * Formulário compartilhado por criar.php e editar.php.
 * Espera: $acao (string URL), $dados (array), $erros (array), $rotuloBotao (string)
 */
?>
<form method="post" action="<?= e($acao) ?>" class="cartao formulario" novalidate>
    <?= csrf_field() ?>

    <label for="nome">Nome <span class="obrigatorio">*</span></label>
    <input type="text" id="nome" name="nome" maxlength="120" required
           value="<?= e($dados['nome']) ?>"
           class="<?= isset($erros['nome']) ? 'invalido' : '' ?>">
    <?php if (isset($erros['nome'])): ?><small class="erro"><?= e($erros['nome']) ?></small><?php endif; ?>

    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" maxlength="180"
           value="<?= e($dados['email']) ?>"
           class="<?= isset($erros['email']) ? 'invalido' : '' ?>">
    <?php if (isset($erros['email'])): ?><small class="erro"><?= e($erros['email']) ?></small><?php endif; ?>

    <label for="telefone">Telefone</label>
    <input type="text" id="telefone" name="telefone" maxlength="30" placeholder="(11) 90000-0000"
           value="<?= e($dados['telefone']) ?>"
           class="<?= isset($erros['telefone']) ? 'invalido' : '' ?>">
    <?php if (isset($erros['telefone'])): ?><small class="erro"><?= e($erros['telefone']) ?></small><?php endif; ?>

    <label for="notas">Notas</label>
    <textarea id="notas" name="notas" rows="5" maxlength="5000"
              class="<?= isset($erros['notas']) ? 'invalido' : '' ?>"><?= e($dados['notas']) ?></textarea>
    <?php if (isset($erros['notas'])): ?><small class="erro"><?= e($erros['notas']) ?></small><?php endif; ?>

    <div class="acoes">
        <button type="submit" class="btn btn-primario"><?= e($rotuloBotao) ?></button>
        <a class="btn btn-secundario" href="<?= e(url('/contatos/index.php')) ?>">Cancelar</a>
    </div>
</form>
