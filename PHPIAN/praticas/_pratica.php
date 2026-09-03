<?php

// PHPIAN · helper das práticas
// Cada prática faz: require __DIR__ . '/_pratica.php';
// Sem Composer e sem dependência do projeto — o PHPIAN é curso de iniciante e as
// práticas rodam com o PHP puro que o aluno tem na máquina.

declare(strict_types=1);

if (PHP_SAPI === 'cli') {
    // Buffer ligado antes de qualquer echo: permite exercitar session_start() e
    // setcookie() numa prática que também imprime na tela, sem "headers already sent".
    ob_start();
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.save_path', sys_get_temp_dir());
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
        echo "\n-- ", $texto, ' ', str_repeat('-', max(1, 68 - mb_strlen($texto))), "\n";
    }

    $GLOBALS['__resultados'] = [];
    $GLOBALS['__pulos'] = [];

    function checa(string $rotulo, bool $condicao, string $detalhe = ''): void
    {
        $GLOBALS['__resultados'][] = $condicao;
        printf("  [%s] %-50s %s\n", $condicao ? 'OK ' : 'FALHA', $rotulo, $detalhe);
    }

    /** Trecho que DEVE lançar a exceção esperada. */
    function checaExcecao(string $rotulo, string $classeEsperada, callable $trecho): void
    {
        try {
            $trecho();
            checa($rotulo, false, 'não lançou nada');
        } catch (\Throwable $e) {
            checa($rotulo, $e instanceof $classeEsperada, $e::class . ': ' . $e->getMessage());
        }
    }

    function nota(string $texto): void
    {
        echo '     · ', $texto, "\n";
    }

    /**
     * Parte da prática que depende de algo fora do PHP (conta no GitHub, print de
     * tela, papel e caneta). Fica registrada em vez de virar falha silenciosa.
     */
    function manual(string $rotulo, string $porque): void
    {
        $GLOBALS['__pulos'][] = $rotulo;
        printf("  [MANUAL] %-48s %s\n", $rotulo, $porque);
    }

    /** Diretório temporário só desta prática, apagado no fim. */
    function areaTemporaria(string $nome): string
    {
        $dir = sys_get_temp_dir() . '/phpian-pratica-' . $nome;
        if (is_dir($dir)) {
            limparArea($dir);
        }
        mkdir($dir, 0777, true);
        $GLOBALS['__area'] = $dir;

        return $dir;
    }

    function limparArea(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $itens = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($itens as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /**
     * Banco das práticas dos Módulos 6 a 8. Usa o container crm-mysql (o mesmo do
     * PHPAN) mas um banco SEPARADO — `phpian` — para não encostar no crm_produto.
     */
    function bancoDaPratica(): PDO
    {
        // Conecta direto no banco phpian. Criar o banco exige privilégio que o
        // usuário `crm` não tem — e não deve ter: quem cria schema é o DBA, uma
        // vez, não a aplicação a cada execução.
        $dsn = 'mysql:host=127.0.0.1;port=3307;dbname=phpian;charset=utf8mb4';
        try {
            return new PDO($dsn, 'crm', 'crm-estudo', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $e) {
            echo "\n  Esta prática precisa do banco phpian. Uma vez só:\n\n";
            echo "    docker start crm-mysql\n";
            echo "    docker exec crm-mysql mysql -uroot -praiz-estudo -e \\\n";
            echo "      \"CREATE DATABASE IF NOT EXISTS phpian CHARACTER SET utf8mb4;\n";
            echo "       GRANT ALL PRIVILEGES ON phpian.* TO 'crm'@'%'; FLUSH PRIVILEGES;\"\n\n";
            echo '  (', $e->getMessage(), ")\n";
            exit(2);
        }
    }

    function fecharPratica(): void
    {
        if (isset($GLOBALS['__area'])) {
            limparArea($GLOBALS['__area']);
        }
        $r = $GLOBALS['__resultados'];
        $falhas = count(array_filter($r, static fn (bool $x): bool => !$x));
        $pulos = count($GLOBALS['__pulos']);
        echo "\n", str_repeat('-', 72), "\n";
        printf(
            "  %d verificações · %d OK · %d falha(s)%s\n",
            count($r),
            count($r) - $falhas,
            $falhas,
            $pulos > 0 ? " · {$pulos} manual(is)" : ''
        );
        echo str_repeat('-', 72), "\n";
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit($falhas === 0 ? 0 : 1);
    }
}
