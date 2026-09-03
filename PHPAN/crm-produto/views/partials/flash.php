<?php use App\Support\View; ?>
<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash flash--<?= View::e($_SESSION['flash']['tipo']) ?>">
        <?= View::e($_SESSION['flash']['mensagem']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
