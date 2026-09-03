<?php

// PHPIAN · consolidador do índice
// Recalcula os blocos "curso" e "modulos" do aulas.json a partir das aulas já
// importadas e das pastas em disco, e valida o resultado. Rodar depois de
// importar. Não inventa dado: o que ainda não chegou fica null e é reportado.
//
//   php bin/consolidar.php

declare(strict_types=1);

const RAIZ   = __DIR__ . '/..';
const INDICE = RAIZ . '/aulas.json';

$doc = json_decode((string) file_get_contents(INDICE), true, 512, JSON_THROW_ON_ERROR);

/** Pasta de cada módulo — o Módulo 8 tem o nome torto de origem, e fica como está. */
$pastas = [];
foreach ((array) glob(RAIZ . '/Modulo_*', GLOB_ONLYDIR) as $dir) {
    if (preg_match('/Modulo_(\d+)/', basename($dir), $m)) {
        $pastas[(int) $m[1]] = basename($dir);
    }
}

/** Exercícios .php de cada módulo (o mini-crm do Módulo 8 é projeto, não exercício). */
$arquivosDe = static function (int $n) use ($pastas): array {
    if (!isset($pastas[$n])) {
        return [];
    }
    $fora = [];
    foreach ((array) glob(RAIZ . '/' . $pastas[$n] . '/*.php') as $f) {
        $fora[] = $pastas[$n] . '/' . basename($f);
    }
    sort($fora);

    return $fora;
};

$titulosConhecidos = [];
foreach ($doc['modulos'] as $m) {
    if (($m['titulo'] ?? null) !== null) {
        $titulosConhecidos[$m['numero']] = $m['titulo'];
    }
}

$contagem = [];
foreach ($doc['aulas'] as $a) {
    $contagem[$a['modulo']] = ($contagem[$a['modulo']] ?? 0) + 1;
}

$numeros = array_unique(array_merge(array_keys($pastas), array_keys($contagem)));
sort($numeros);

$modulos = [];
foreach ($numeros as $n) {
    $modulos[] = [
        'numero'   => $n,
        'titulo'   => $titulosConhecidos[$n] ?? null,
        'pasta'    => $pastas[$n] ?? null,
        'aulas'    => $contagem[$n] ?? 0,
        'arquivos' => $arquivosDe($n),
    ];
}

$doc['curso'] = [
    'id'            => 'phpian',
    'nome'          => 'PHPIAN — Iniciante',
    'nivel'         => 'Iniciante',
    'plataforma'    => 'cursos.asllanmaciel.com.br',
    'base_url'      => 'https://cursos.asllanmaciel.com.br/cursos/phpian/aula.php',
    'total_modulos' => count($modulos),
    'total_aulas'   => 40,
    'situacao'      => 'concluído',
    'acesso_ate'    => '2027-08-03',
    'projeto_final' => 'Modulo_8(modeagem_final)/mini-crm',
];
$doc['modulos'] = $modulos;
$doc['$schema'] = './aulas.schema.json';
$doc = ['$schema' => $doc['$schema'], 'curso' => $doc['curso'], 'modulos' => $doc['modulos'], 'aulas' => $doc['aulas']];

file_put_contents(INDICE, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

/* --- validação --- */
$erros = [];
$vistos = [];
foreach ($doc['aulas'] as $a) {
    if (isset($vistos[$a['id']])) {
        $erros[] = "aula {$a['id']} duplicada";
    }
    $vistos[$a['id']] = true;
    if ($a['id'] !== $a['modulo'] . '-' . $a['numero']) {
        $erros[] = "aula {$a['id']}: id não bate com modulo/numero";
    }
    if (($a['titulo'] ?? '') === '') {
        $erros[] = "aula {$a['id']}: sem título";
    }
    foreach ($a['arquivos'] as $f) {
        if (!is_file(RAIZ . '/' . $f)) {
            $erros[] = "aula {$a['id']}: arquivo inexistente -> {$f}";
        }
    }
    // a navegação tem que amarrar: a próxima de X precisa apontar de volta para X
    $prox = $a['navegacao']['proxima'] ?? null;
    if ($prox !== null && isset($vistos[$prox])) {
        $erros[] = "aula {$a['id']}: 'proxima' aponta para trás ({$prox})";
    }
}
foreach ($doc['aulas'] as $a) {
    $prox = $a['navegacao']['proxima'] ?? null;
    if ($prox !== null && isset($vistos[$prox])) {
        $alvo = null;
        foreach ($doc['aulas'] as $b) {
            if ($b['id'] === $prox) {
                $alvo = $b;
            }
        }
        if ($alvo !== null && ($alvo['navegacao']['anterior'] ?? null) !== $a['id']) {
            $erros[] = "corrente quebrada: {$a['id']} -> {$prox}, mas {$prox} volta para " . var_export($alvo['navegacao']['anterior'] ?? null, true);
        }
    }
}

$importadas = count($doc['aulas']);
$faltam = $doc['curso']['total_aulas'] - $importadas;

echo "módulos: " . count($modulos) . " | aulas importadas: {$importadas}/{$doc['curso']['total_aulas']}";
echo $faltam > 0 ? " (faltam {$faltam})\n" : "\n";
foreach ($modulos as $m) {
    printf(
        "  Módulo %d — %-42s %d aula(s), %d exercício(s)\n",
        $m['numero'],
        $m['titulo'] ?? '(título ainda não importado)',
        $m['aulas'],
        count($m['arquivos'])
    );
}
echo $erros === []
    ? "\nvalidação do índice: 0 erros\n"
    : "\nvalidação do índice: " . count($erros) . " erro(s)\n  " . implode("\n  ", $erros) . "\n";

/* --- saúde dos exercícios em disco --- */
$doentes = [];
foreach ($modulos as $m) {
    foreach ($m['arquivos'] as $rel) {
        $abs = RAIZ . '/' . $rel;
        if (filesize($abs) === 0) {
            $doentes[] = "{$rel}: arquivo vazio";
            continue;
        }
        exec('php -l ' . escapeshellarg($abs) . ' 2>&1', $saida, $codigo);
        if ($codigo !== 0) {
            $doentes[] = "{$rel}: " . preg_replace('/ in \/.*/', '', $saida[0] ?? 'não compila');
        }
        $saida = [];
    }
}
// Arquivo com '/' no nome vira pasta no Linux — o título da aula foi usado como
// nome de arquivo e o sistema partiu em dois. Vale reportar, não corrigir sozinho.
foreach ((array) glob(RAIZ . '/Modulo_*/*/*.php') as $perdido) {
    $rel = substr($perdido, strlen(RAIZ) + 1);
    if (!str_contains($rel, 'mini-crm')) {
        $doentes[] = "{$rel}: exercício dentro de subpasta (nome de arquivo com '/' virou diretório)";
    }
}

echo $doentes === []
    ? "exercícios: todos compilam\n"
    : "exercícios com problema: " . count($doentes) . "\n  " . implode("\n  ", $doentes) . "\n";
