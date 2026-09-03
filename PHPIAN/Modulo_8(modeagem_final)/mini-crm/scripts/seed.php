<?php
declare(strict_types=1);

/**
 * Cria o usuário administrador inicial.
 *
 *   php scripts/seed.php
 *   php scripts/seed.php --nome="Maria" --email=maria@exemplo.com --senha=segredo123
 *   php scripts/seed.php --email=admin@minicrm.local --senha=nova12345 --redefinir
 *
 * A senha é gravada com password_hash — nunca em texto puro.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script roda apenas pela linha de comando.');
}

require __DIR__ . '/../src/bootstrap.php';

$opcoes = getopt('', ['nome::', 'email::', 'senha::', 'redefinir']);

$nome  = (string) ($opcoes['nome']  ?? 'Administrador');
$email = (string) ($opcoes['email'] ?? 'admin@minicrm.local');
$senha = (string) ($opcoes['senha'] ?? 'admin12345');
$redefinir = array_key_exists('redefinir', $opcoes);

// Tabelas existem?
try {
    db()->query('SELECT 1 FROM users LIMIT 1');
} catch (PDOException $erro) {
    exit("Tabela 'users' não encontrada. Rode antes:\n  mysql -u "
        . config('db.user') . " -p " . config('db.name') . " < sql/schema.sql\n");
}

if (usuario_email_existe($email)) {
    if (!$redefinir) {
        exit("Já existe usuário com o e-mail {$email}.\n"
            . "Use --redefinir para trocar a senha dele, ou --email=outro@exemplo.com.\n");
    }

    if (mb_strlen($senha) < 8) {
        exit("Senha muito curta: mínimo de 8 caracteres.\n");
    }

    db()->prepare('UPDATE users SET senha_hash = ? WHERE email = ?')
        ->execute([password_hash($senha, PASSWORD_DEFAULT), $email]);

    echo "Senha redefinida para {$email}.\n";
    exit(0);
}

// Mesma validação usada pelo cadastro na web — sem regra duplicada.
$erros = usuario_validar($nome, $email, $senha);
if ($erros) {
    echo "Não foi possível criar o usuário:\n";
    foreach ($erros as $campo => $mensagem) {
        echo "  - {$campo}: {$mensagem}\n";
    }
    exit(1);
}

$id = usuario_criar($nome, $email, $senha);

echo "Usuário administrador criado.\n";
echo "  id:    {$id}\n";
echo "  nome:  {$nome}\n";
echo "  email: {$email}\n";
echo "  senha: {$senha}\n";
echo "\nTroque essa senha depois do primeiro login.\n";
