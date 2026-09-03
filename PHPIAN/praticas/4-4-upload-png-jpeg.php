<?php

// PHPIAN · Módulo 4 · Aula 4 — Upload de arquivos
// Prática: "Implemente upload só de PNG/JPEG até 2MB, salvando em pasta uploads/."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 4-4 — upload de PNG/JPEG até 2MB');

secao('A função de upload');

const LIMITE_BYTES = 2 * 1024 * 1024;

/** @return array{ok: bool, erro?: string, nome?: string} */
function receberUpload(array $arquivo, string $destino): array
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Falha no envio'];
    }
    if (($arquivo['size'] ?? 0) > LIMITE_BYTES) {
        return ['ok' => false, 'erro' => 'Arquivo maior que 2MB'];
    }

    // O ponto da aula: o MIME real vem do CONTEÚDO, nunca da extensão nem do
    // Content-Type que o navegador manda (ambos são escolhidos pelo atacante).
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($permitidos[$mime])) {
        return ['ok' => false, 'erro' => "Tipo inválido: {$mime}"];
    }

    // Nome novo e aleatório: o nome original pode conter ../, .php, ou colidir.
    $nome = bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];
    copy($arquivo['tmp_name'], $destino . '/' . $nome);

    return ['ok' => true, 'nome' => $nome];
}

$area = areaTemporaria('4-4');
$uploads = $area . '/uploads';
mkdir($uploads);

// Imagens de verdade, byte a byte. Não usa GD de propósito: a extensão não está
// em toda máquina, e o finfo só precisa dos bytes mágicos do cabeçalho — que são
// exatamente estes.
$png = $area . '/foto.png';
file_put_contents($png, base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
));
$jpg = $area . '/foto.jpg';
file_put_contents($jpg, base64_decode(
    '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
    . 'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
    . 'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='
));

$fake = static fn (string $caminho, ?int $tamanho = null): array => [
    'tmp_name' => $caminho,
    'error' => UPLOAD_ERR_OK,
    'size' => $tamanho ?? filesize($caminho),
    'name' => basename($caminho),
];

secao('O que deve passar');

$r = receberUpload($fake($png), $uploads);
checa('PNG aceito', $r['ok'] === true, $r['nome'] ?? ($r['erro'] ?? ''));
checa('salvou com extensão .png', str_ends_with($r['nome'] ?? '', '.png'));
checa('o arquivo está no disco', is_file($uploads . '/' . $r['nome']));

$r2 = receberUpload($fake($jpg), $uploads);
checa('JPEG aceito', $r2['ok'] === true);
checa('salvou com extensão .jpg', str_ends_with($r2['nome'] ?? '', '.jpg'));

secao('"Nunca confie na extensão" — o teste que prova');

// Um .php renomeado para .png. A extensão mente; o finfo não.
$disfarcado = $area . '/backdoor.png';
file_put_contents($disfarcado, "<?php system(\$_GET['cmd']); ?>\n");
$r3 = receberUpload($fake($disfarcado), $uploads);
checa('PHP disfarçado de .png é RECUSADO', $r3['ok'] === false, $r3['erro'] ?? '');
checa('nada foi salvo', count(glob($uploads . '/*')) === 2, 'continuam só o png e o jpg legítimos');

// GIF é imagem de verdade, mas não está na lista permitida.
$gif = $area . '/foto.gif';
file_put_contents($gif, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
checa('GIF é recusado (fora da lista)', receberUpload($fake($gif), $uploads)['ok'] === false);

// Texto puro
$txt = $area . '/nota.txt';
file_put_contents($txt, 'só texto');
checa('texto puro é recusado', receberUpload($fake($txt), $uploads)['ok'] === false);

secao('O limite de 2MB');

checa('exatamente 2MB passa', receberUpload($fake($png, LIMITE_BYTES), $uploads)['ok'] === true);
$grande = receberUpload($fake($png, LIMITE_BYTES + 1), $uploads);
checa('2MB + 1 byte é recusado', $grande['ok'] === false, $grande['erro'] ?? '');
checa('a mensagem diz o limite', str_contains($grande['erro'] ?? '', '2MB'));

secao('Nome novo: por que não reaproveitar o do usuário');

$nomes = [];
for ($i = 0; $i < 5; $i++) {
    $nomes[] = receberUpload($fake($png), $uploads)['nome'];
}
checa('5 envios geraram 5 nomes distintos', count(array_unique($nomes)) === 5, 'nenhum sobrescreveu o outro');
checa('nenhum nome tem caminho', !array_filter($nomes, static fn ($n) => str_contains($n, '/')), 'sem ../ possível');
checa('nome é hex + extensão', (bool) preg_match('/^[0-9a-f]{16}\.(png|jpg)$/', $nomes[0]), $nomes[0]);

secao('Erros de envio que não são culpa do arquivo');

foreach ([UPLOAD_ERR_INI_SIZE => 'maior que upload_max_filesize', UPLOAD_ERR_PARTIAL => 'envio interrompido', UPLOAD_ERR_NO_FILE => 'nada selecionado'] as $codigo => $oQue) {
    $r = receberUpload(['tmp_name' => $png, 'error' => $codigo, 'size' => 100, 'name' => 'x.png'], $uploads);
    checa("erro {$codigo} ({$oQue}) é tratado", $r['ok'] === false);
}

secao('O aviso: salvar fora da raiz pública');

// Se uploads/ fica dentro de public/, um .php que passasse pelo filtro seria
// EXECUTÁVEL pela URL. Fora da raiz, no máximo é lido — e só se o código deixar.
checa('uploads/ está fora de qualquer public/', !str_contains($uploads, '/public/'));
nota('a aula sugere salvar fora da raiz pública, e servir por um script que lê o arquivo');

fecharPratica();
