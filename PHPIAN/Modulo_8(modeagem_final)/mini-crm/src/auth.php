<?php
declare(strict_types=1);

/** Inicia a sessão com cookie endurecido. Chamado pelo bootstrap. */
function sessao_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'httponly' => true,                          // JS não lê o cookie
        'samesite' => 'Lax',                         // freia CSRF vindo de outro site
        'secure'   => !empty($_SERVER['HTTPS']),     // só HTTPS quando houver HTTPS
    ]);
    session_name('minicrm_sessao');
    session_start();
}

/** Porteiro da área autenticada. */
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        flash_set('erro', 'Faça login para acessar o CRM.');
        redirect('/login.php');
    }
}

/** Inverso: quem já está logado não vê login/cadastro. */
function requireGuest(): void
{
    if (!empty($_SESSION['user_id'])) {
        redirect('/contatos/index.php');
    }
}

/** ID do usuário logado. Toda query de contato é filtrada por ele. */
function auth_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/** Dados do usuário logado (guardados na sessão no login). */
function auth_user(): ?array
{
    return auth_id() > 0
        ? ['id' => auth_id(), 'nome' => $_SESSION['user_nome'] ?? '', 'email' => $_SESSION['user_email'] ?? '']
        : null;
}

/** Valida e-mail + senha e abre a sessão. */
function auth_login(string $email, string $senha): bool
{
    $consulta = db()->prepare('SELECT id, nome, email, senha_hash FROM users WHERE email = ? LIMIT 1');
    $consulta->execute([$email]);
    $usuario = $consulta->fetch();

    // password_verify em tempo constante; mensagem genérica em quem chama.
    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    // Hash antigo? Atualiza de graça no login.
    if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE users SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($senha, PASSWORD_DEFAULT), $usuario['id']]);
    }

    session_regenerate_id(true); // evita fixação de sessão
    $_SESSION['user_id']    = (int) $usuario['id'];
    $_SESSION['user_nome']  = $usuario['nome'];
    $_SESSION['user_email'] = $usuario['email'];

    return true;
}

/** Encerra a sessão por completo. */
function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/** Valida dados de usuário. Usada pelo cadastro e pelo seed. */
function usuario_validar(string $nome, string $email, string $senha): array
{
    $erros = [];

    if ($nome === '') {
        $erros['nome'] = 'Informe o nome.';
    } elseif (mb_strlen($nome) > 120) {
        $erros['nome'] = 'Máximo de 120 caracteres.';
    }

    if ($email === '') {
        $erros['email'] = 'Informe o e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido.';
    } elseif (mb_strlen($email) > 180) {
        $erros['email'] = 'Máximo de 180 caracteres.';
    } elseif (usuario_email_existe($email)) {
        $erros['email'] = 'Este e-mail já está cadastrado.';
    }

    if (mb_strlen($senha) < 8) {
        $erros['senha'] = 'A senha precisa de no mínimo 8 caracteres.';
    }

    return $erros;
}

function usuario_email_existe(string $email): bool
{
    $consulta = db()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $consulta->execute([$email]);
    return (bool) $consulta->fetchColumn();
}

/** Cria usuário com senha em hash. Ponto único usado por cadastro e seed. */
function usuario_criar(string $nome, string $email, string $senha): int
{
    db()->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)')
        ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);

    return (int) db()->lastInsertId();
}
