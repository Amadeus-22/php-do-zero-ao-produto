<?php
declare(strict_types=1);

/** Lê a configuração com notação de ponto: config('db.host'). */
function config(string $chave, mixed $padrao = null): mixed
{
    static $dados = null;

    if ($dados === null) {
        $arquivo = APP_ROOT . '/config/app.php';
        if (!is_file($arquivo)) {
            http_response_code(500);
            exit('Configuração ausente: copie config/app.example.php para config/app.php');
        }
        $dados = require $arquivo;
    }

    $valor = $dados;
    foreach (explode('.', $chave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return $padrao;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/** Escapa para HTML. Toda saída dinâmica passa por aqui. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Caminho base da aplicação na URL (ex.: /mini-crm/public). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configurado = config('base_url');
    if (is_string($configurado)) {
        return $base = rtrim($configurado, '/');
    }

    $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $publico = realpath(PUBLIC_ROOT);

    if ($docroot && $publico && str_starts_with($publico, $docroot)) {
        return $base = rtrim(str_replace('\\', '/', substr($publico, strlen($docroot))), '/');
    }
    return $base = '';
}

/** Monta uma URL interna: url('/contatos/index.php'). */
function url(string $caminho = '/'): string
{
    return base_path() . '/' . ltrim($caminho, '/');
}

/** Redireciona e encerra (padrão POST -> Redirect -> GET). */
function redirect(string $caminho): never
{
    header('Location: ' . url($caminho));
    exit;
}

/** Guarda uma mensagem para a próxima requisição. Tipos: ok | erro */
function flash_set(string $tipo, string $mensagem): void
{
    $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Devolve e limpa as mensagens pendentes. */
function flash_all(): array
{
    $mensagens = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $mensagens;
}

/** Campo de $_POST como string já trimada. */
function post(string $campo, string $padrao = ''): string
{
    $valor = $_POST[$campo] ?? null;
    return is_string($valor) ? trim($valor) : $padrao;
}

/** Campo de $_GET como string já trimada. */
function query(string $campo, string $padrao = ''): string
{
    $valor = $_GET[$campo] ?? null;
    return is_string($valor) ? trim($valor) : $padrao;
}

/** Data do banco (Y-m-d H:i:s) para exibição pt-BR. */
function data_br(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $d = date_create($datetime);
    return $d ? $d->format('d/m/Y H:i') : '';
}

/** Interrompe a requisição com uma página de erro simples (404, 403...). */
function abortar(int $codigo, string $mensagem): never
{
    http_response_code($codigo);

    $titulo = 'Ops';
    require APP_ROOT . '/templates/header.php';
    ?>
    <div class="cartao cartao-estreito">
        <h1><?= e($codigo) ?></h1>
        <p class="subtitulo"><?= e($mensagem) ?></p>
        <a class="btn btn-secundario" href="<?= e(url('/contatos/index.php')) ?>">Voltar para a lista</a>
    </div>
    <?php
    require APP_ROOT . '/templates/footer.php';
    exit;
}
