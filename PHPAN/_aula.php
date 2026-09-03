<?php

/**
 * Helper de saída das aulas do PHPAN.
 * Cada arquivo de aula faz: require __DIR__ . '/../_aula.php';
 */

declare(strict_types=1);

// Buffer de saída ligado desde o início: com ele, o PHP ainda aceita cookie e
// cabeçalho DEPOIS de já termos impresso texto — é o que permite exercitar
// session_start(), session_regenerate_id() e setcookie() dentro de uma aula que
// imprime resultado na tela. Sem isto: "headers already sent".
if (PHP_SAPI === 'cli') {
    ob_start();

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.save_path', sys_get_temp_dir());
        // mesmas flags que App\Auth\Sessao aplica em produção
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        session_start();
    }
}

if (!function_exists('titulo')) {
    function titulo(string $texto): void
    {
        echo "\n", str_repeat('=', 72), "\n  ", mb_strtoupper($texto), "\n", str_repeat('=', 72), "\n";
    }

    function secao(string $texto): void
    {
        echo "\n-- ", $texto, " ", str_repeat('-', max(1, 68 - mb_strlen($texto))), "\n";
    }

    /** @var list<bool> $GLOBALS['__resultados'] */
    $GLOBALS['__resultados'] = [];

    function checa(string $rotulo, bool $condicao, string $detalhe = ''): void
    {
        $GLOBALS['__resultados'][] = $condicao;
        printf(
            "  [%s] %-52s %s\n",
            $condicao ? 'OK ' : 'FALHA',
            $rotulo,
            $detalhe,
        );
    }

    /** Executa um trecho que DEVE lançar a exceção esperada. */
    function checaExcecao(string $rotulo, string $classeEsperada, callable $trecho): void
    {
        try {
            $trecho();
            checa($rotulo, false, 'não lançou nada');
        } catch (\Throwable $e) {
            checa(
                $rotulo,
                $e instanceof $classeEsperada,
                $e::class . ': ' . $e->getMessage(),
            );
        }
    }

    function nota(string $texto): void
    {
        echo "     · ", $texto, "\n";
    }

    /**
     * Prepara o banco para uma aula que precisa dele. Se estiver fora do ar,
     * a aula avisa e encerra em vez de estourar exceção crua.
     */
    function bancoDaAula(): PDO
    {
        try {
            App\Config\Config::carregar();
            $pdo = App\Support\Database::conexao();
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            echo "\n  Esta aula precisa do banco. Suba com:  docker start crm-mysql\n";
            echo '  (', $e->getMessage(), ")\n";
            exit(2);
        }

        foreach (['auditoria', 'jobs', 'lembretes', 'anexos', 'tokens', 'resets_senha', 'tentativas_login', 'atividades', 'contatos', 'clientes', 'usuarios'] as $t) {
            $pdo->exec("DELETE FROM {$t}");
            // DELETE não reseta o AUTO_INCREMENT: sem isto o primeiro cliente de
            // uma aula teria id 37, e qualquer verificação de "id === 1" quebraria
            // dependendo de quantas vezes a aula já rodou.
            $pdo->exec("ALTER TABLE {$t} AUTO_INCREMENT = 1");
        }

        App\Support\Container::usar(new App\Infrastructure\Cliente\RepositorioDeClientesPdo($pdo));
        App\Support\Container::usarUsuarios(new App\Infrastructure\Usuario\RepositorioDeUsuariosPdo($pdo));

        return $pdo;
    }

    /** Cria um usuário de cada papel e devolve o access token de cada um. */
    function tokensPorPapel(): array
    {
        $tokens = [];

        foreach (App\Domain\Usuario\Papel::cases() as $papel) {
            $usuario = App\Support\Container::repositorioDeUsuarios()->salvar(
                App\Domain\Usuario\Usuario::novo(ucfirst($papel->value), "{$papel->value}@exemplo.com", 'senha-de-estudo', $papel),
            );
            $tokens[$papel->value] = App\Support\Container::tokenService()->emitirPar((int) $usuario->id())['access'];
        }

        return $tokens;
    }

    /**
     * Sessão de painel simulada — para aulas dos Módulos 1 a 4, escritas antes de
     * o Módulo 5 exigir login. O sistema evoluiu; as aulas anteriores continuam
     * válidas, só precisam entrar autenticadas.
     */
    function logadoNoPainel(string $papel = 'admin'): void
    {
        $_SESSION['usuario_id'] = 1;
        $_SESSION['papel'] = $papel;
        $_SESSION['criado_em'] = time();
    }

    /** Token de acesso para exercitar a API nas aulas anteriores ao Módulo 5. */
    function tokenDeAula(string $papel = 'admin'): string
    {
        $repo = App\Support\Container::repositorioDeUsuarios();
        $email = "{$papel}@exemplo.com";
        $usuario = $repo->buscarPorEmail($email)
            ?? $repo->salvar(App\Domain\Usuario\Usuario::novo(
                ucfirst($papel),
                $email,
                'senha-de-estudo',
                App\Domain\Usuario\Papel::from($papel),
            ));

        return App\Support\Container::tokenService()->emitirPar((int) $usuario->id())['access'];
    }

    function fecharAula(): void
    {
        $r = $GLOBALS['__resultados'];
        $falhas = count(array_filter($r, static fn (bool $x): bool => !$x));
        echo "\n", str_repeat('-', 72), "\n";
        printf("  %d verificações · %d OK · %d falha(s)\n", count($r), count($r) - $falhas, $falhas);
        echo str_repeat('-', 72), "\n";

        if (ob_get_level() > 0) {
            ob_end_flush();
        }

        exit($falhas === 0 ? 0 : 1);
    }
}
