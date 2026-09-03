<?php
declare(strict_types=1);

/**
 * Repositório de contatos.
 *
 * REGRA DE OURO (ownership): toda consulta recebe $userId e filtra por ele —
 * inclusive UPDATE e DELETE. Não existe acesso a contato sem dono aqui dentro.
 */

/** Lista os contatos do usuário, opcionalmente filtrando por nome/e-mail. */
function contatos_listar(int $userId, string $busca = ''): array
{
    $sql = 'SELECT id, nome, email, telefone, notas, criado_em
              FROM contatos
             WHERE user_id = ?';
    $parametros = [$userId];

    if ($busca !== '') {
        $sql .= ' AND (nome LIKE ? OR email LIKE ?)';
        $termo = '%' . like_escape($busca) . '%';
        $parametros[] = $termo;
        $parametros[] = $termo;
    }

    $sql .= ' ORDER BY nome ASC';

    $consulta = db()->prepare($sql);
    $consulta->execute($parametros);

    return $consulta->fetchAll();
}

/** Busca um contato do usuário. null = não existe OU é de outro dono. */
function contato_buscar(int $id, int $userId): ?array
{
    $consulta = db()->prepare(
        'SELECT id, nome, email, telefone, notas, criado_em
           FROM contatos
          WHERE id = ? AND user_id = ?
          LIMIT 1'
    );
    $consulta->execute([$id, $userId]);

    return $consulta->fetch() ?: null;
}

function contato_criar(int $userId, array $dados): int
{
    db()->prepare(
        'INSERT INTO contatos (user_id, nome, email, telefone, notas)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        $dados['nome'],
        $dados['email']    ?: null,
        $dados['telefone'] ?: null,
        $dados['notas']    ?: null,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Ownership garantido pelo WHERE. A página confirma antes, com contato_buscar(),
 * que o registro existe — por isso aqui não há retorno a interpretar
 * (um UPDATE com os mesmos valores devolve rowCount 0 e não é erro).
 */
function contato_atualizar(int $id, int $userId, array $dados): void
{
    db()->prepare(
        'UPDATE contatos
            SET nome = ?, email = ?, telefone = ?, notas = ?
          WHERE id = ? AND user_id = ?'
    )->execute([
        $dados['nome'],
        $dados['email']    ?: null,
        $dados['telefone'] ?: null,
        $dados['notas']    ?: null,
        $id,
        $userId,
    ]);
}

/** false = nada excluído (id inexistente ou de outro usuário). */
function contato_excluir(int $id, int $userId): bool
{
    $consulta = db()->prepare('DELETE FROM contatos WHERE id = ? AND user_id = ?');
    $consulta->execute([$id, $userId]);

    return $consulta->rowCount() === 1;
}

/**
 * Validação única, compartilhada por criar.php e editar.php.
 * Retorna [erros, dadosLimpos].
 */
function contato_validar(array $entrada): array
{
    $dados = [
        'nome'     => trim((string) ($entrada['nome']     ?? '')),
        'email'    => trim((string) ($entrada['email']    ?? '')),
        'telefone' => trim((string) ($entrada['telefone'] ?? '')),
        'notas'    => trim((string) ($entrada['notas']    ?? '')),
    ];

    $erros = [];

    if ($dados['nome'] === '') {
        $erros['nome'] = 'Informe o nome.';
    } elseif (mb_strlen($dados['nome']) > 120) {
        $erros['nome'] = 'Máximo de 120 caracteres.';
    }

    if ($dados['email'] !== '') {
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'E-mail inválido.';
        } elseif (mb_strlen($dados['email']) > 180) {
            $erros['email'] = 'Máximo de 180 caracteres.';
        }
    }

    if ($dados['telefone'] !== '') {
        if (mb_strlen($dados['telefone']) > 30) {
            $erros['telefone'] = 'Máximo de 30 caracteres.';
        } elseif (!preg_match('/^[0-9()+\-\s.]+$/', $dados['telefone'])) {
            $erros['telefone'] = 'Use apenas números, espaços e os sinais ( ) + - .';
        }
    }

    if (mb_strlen($dados['notas']) > 5000) {
        $erros['notas'] = 'Máximo de 5000 caracteres.';
    }

    return [$erros, $dados];
}

/** Neutraliza os curingas % e _ dentro de um termo de busca LIKE. */
function like_escape(string $termo): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termo);
}
