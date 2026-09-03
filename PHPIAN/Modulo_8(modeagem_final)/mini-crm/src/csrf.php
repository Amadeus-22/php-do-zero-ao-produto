<?php
declare(strict_types=1);

/** Token da sessão (criado uma vez). */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Campo oculto para colar dentro de todo <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Barra a requisição se o token não bater. Chamar no topo de todo POST. */
function csrf_check(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(419);
        exit('Token de segurança inválido. Volte, recarregue a página e tente de novo.');
    }
}
