<?php

// PHPIAN · importador de aula
// Lê o HTML de uma aula da plataforma (arquivo ou stdin) e grava/atualiza a
// entrada correspondente em aulas.json. Idempotente: rodar de novo sobrescreve
// só aquela aula, preservando o resto do arquivo e a ordem por módulo/número.
//
//   php bin/importar-aula.php aula.html
//   pbpaste | php bin/importar-aula.php
//   php bin/importar-aula.php html/*.html

declare(strict_types=1);

const RAIZ     = __DIR__ . '/..';
const INDICE   = RAIZ . '/aulas.json';
const BASE_URL = 'https://cursos.asllanmaciel.com.br/cursos/phpian/aula.php';

/** Texto limpo de um nó: entidades resolvidas, espaços normalizados. */
function texto(?DOMNode $no): string
{
    if ($no === null) {
        return '';
    }

    return trim(preg_replace('/\s+/u', ' ', $no->textContent) ?? '');
}

/** @return array<int, string> */
function itens(DOMElement $lista): array
{
    $fora = [];
    foreach ($lista->getElementsByTagName('li') as $li) {
        $t = texto($li);
        if ($t !== '') {
            $fora[] = $t;
        }
    }

    return $fora;
}

/** @return array<string, mixed> */
function extrair(string $html): array
{
    $doc = new DOMDocument();
    // O trecho colado é fragmento, não documento: sem o wrapper o DOM assume
    // latin-1 e os acentos viram lixo.
    $ok = @$doc->loadHTML(
        '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    if (!$ok) {
        throw new RuntimeException('HTML não pôde ser lido');
    }
    $x = new DOMXPath($doc);

    $um = static fn (string $q): ?DOMElement => $x->query($q)->item(0) instanceof DOMElement
        ? $x->query($q)->item(0)
        : null;

    // id da aula: o link de suporte é a fonte mais confiável (lesson=2-3)
    $id = null;
    foreach ($x->query('//a[@href]') as $a) {
        if (preg_match('/[?&]lesson=(\d+-\d+)/', html_entity_decode($a->getAttribute('href')), $m)) {
            $id = $m[1];
            break;
        }
    }
    if ($id === null) {
        throw new RuntimeException('não achei o id da aula (link de suporte ausente)');
    }
    [$modulo, $numero] = array_map('intval', explode('-', $id));

    // eyebrow: "Módulo 2 · 16 min"
    $duracao = null;
    if (preg_match('/(\d+)\s*min/u', texto($um('//p[@class="eyebrow"]')), $m)) {
        $duracao = (int) $m[1];
    }

    $titulo = texto($um('//h1'));
    $corpo  = $um('//div[@class="lesson-body"]');
    if ($corpo === null) {
        throw new RuntimeException("aula {$id}: sem div.lesson-body");
    }

    $resumo = null;
    $secoes = [];
    $codigo = [];
    $callouts = [];
    $pratica = null;
    $secaoAtual = null;

    $fecharSecao = static function () use (&$secaoAtual, &$secoes): void {
        if ($secaoAtual !== null) {
            $secoes[] = $secaoAtual;
            $secaoAtual = null;
        }
    };

    foreach ($corpo->childNodes as $no) {
        if (!$no instanceof DOMElement) {
            continue;
        }
        $classe = $no->getAttribute('class');
        $tag    = strtolower($no->tagName);

        // h3 é subtítulo dentro do h2; vira seção própria com nivel 3, para não
        // sumir e para o leitor saber que ela pende da seção anterior.
        if ($tag === 'h2' || $tag === 'h3') {
            $fecharSecao();
            $secaoAtual = [
                'titulo'     => texto($no),
                'nivel'      => $tag === 'h3' ? 3 : 2,
                'paragrafos' => [],
                'itens'      => [],
            ];
            continue;
        }

        if ($tag === 'pre' && str_contains($classe, 'code-block')) {
            $cod = $no->getElementsByTagName('code')->item(0);
            if ($cod !== null) {
                // textContent já resolve &lt; &gt; &amp; — é o código como o aluno copia
                $codigo[] = [
                    'aula_secao' => $secaoAtual['titulo'] ?? null,
                    'conteudo'   => rtrim($cod->textContent),
                ];
            }
            continue;
        }

        if (str_contains($classe, 'callout')) {
            $forte = $no->getElementsByTagName('strong')->item(0);
            $tituloCallout = texto($forte);
            $completo = texto($no);
            $callouts[] = [
                'tipo'   => str_contains($classe, 'warn') ? 'aviso' : 'nota',
                'titulo' => $tituloCallout !== '' ? $tituloCallout : null,
                'texto'  => trim(substr($completo, strlen($tituloCallout))),
            ];
            continue;
        }

        if (str_contains($classe, 'practice')) {
            $p = $no->getElementsByTagName('p')->item(0);
            $pratica = texto($p);
            continue;
        }

        if ($tag === 'p') {
            $t = texto($no);
            if ($t === '') {
                continue;
            }
            if ($secaoAtual !== null) {
                $secaoAtual['paragrafos'][] = $t;
            } elseif ($resumo === null) {
                $resumo = $t;
            } else {
                $secoes[] = ['titulo' => null, 'nivel' => null, 'paragrafos' => [$t], 'itens' => []];
            }
            continue;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $lista = itens($no);
            if ($secaoAtual !== null) {
                $secaoAtual['itens'] = array_merge($secaoAtual['itens'], $lista);
            } else {
                $secoes[] = ['titulo' => null, 'nivel' => null, 'paragrafos' => [], 'itens' => $lista];
            }
        }
    }
    $fecharSecao();

    $vizinho = static function (string $q) use ($x): ?string {
        $a = $x->query($q)->item(0);
        if ($a instanceof DOMElement && preg_match('/#(\d+-\d+)$/', $a->getAttribute('href'), $m)) {
            return $m[1];
        }

        return null;
    };

    return [
        'id'           => $id,
        'modulo'       => $modulo,
        'numero'       => $numero,
        'titulo'       => $titulo,
        'url'          => BASE_URL . '#' . $id,
        'duracao_min'  => $duracao,
        'resumo'       => $resumo,
        'secoes'       => $secoes,
        'codigo'       => $codigo,
        'callouts'     => $callouts,
        'pratica'      => $pratica,
        'navegacao'    => [
            'anterior' => $vizinho('//nav[@class="lesson-nav"]//a[contains(., "Anterior")]'),
            'proxima'  => $vizinho('//nav[@class="lesson-nav"]//a[@id="lesson-next"]'),
        ],
        'arquivos'     => [],
        // preenchido à mão depois: o arquivo em praticas/ que resolve a prática
        'pratica_arquivo' => null,
    ];
}

/* ------------------------------------------------------------------ */

$entradas = array_slice($argv, 1);
$htmls = [];
if ($entradas === []) {
    $htmls['(stdin)'] = (string) stream_get_contents(STDIN);
} else {
    foreach ($entradas as $caminho) {
        if (!is_file($caminho)) {
            fwrite(STDERR, "arquivo não encontrado: {$caminho}\n");
            exit(1);
        }
        $htmls[$caminho] = (string) file_get_contents($caminho);
    }
}

$doc = is_file(INDICE)
    ? json_decode((string) file_get_contents(INDICE), true, 512, JSON_THROW_ON_ERROR)
    : ['$schema' => './aulas.schema.json', 'curso' => [], 'modulos' => [], 'aulas' => []];

$porId = [];
foreach ($doc['aulas'] as $a) {
    $porId[$a['id']] = $a;
}

$novas = 0;
$atualizadas = 0;
foreach ($htmls as $origem => $html) {
    try {
        $aula = extrair($html);
    } catch (RuntimeException $e) {
        fwrite(STDERR, "! {$origem}: {$e->getMessage()}\n");
        continue;
    }
    // Campos preenchidos à mão — qual exercício do repositório é desta aula, e
    // qual arquivo em praticas/ resolve a prática. Um reimport traz o conteúdo
    // novo da plataforma, mas não pode apagar esse trabalho.
    if (isset($porId[$aula['id']])) {
        foreach (['arquivos', 'pratica_arquivo'] as $preservado) {
            if (array_key_exists($preservado, $porId[$aula['id']])) {
                $aula[$preservado] = $porId[$aula['id']][$preservado];
            }
        }
        $atualizadas++;
    } else {
        $novas++;
    }
    $porId[$aula['id']] = $aula;
    echo sprintf("  %-5s %s (%s min)\n", $aula['id'], $aula['titulo'], $aula['duracao_min'] ?? '?');
}

$aulas = array_values($porId);
usort($aulas, static fn ($a, $b) => [$a['modulo'], $a['numero']] <=> [$b['modulo'], $b['numero']]);
$doc['aulas'] = $aulas;

file_put_contents(
    INDICE,
    json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "\naulas.json: {$novas} nova(s), {$atualizadas} atualizada(s), " . count($aulas) . " no total\n";
