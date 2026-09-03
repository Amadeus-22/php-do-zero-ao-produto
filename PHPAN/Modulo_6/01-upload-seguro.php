<?php

// PHPAN · Módulo 6 · Aula 01 — Upload seguro de anexos
// metadados em aulas.json · a ideia em 01-upload-seguro.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Uploads\UploadInvalido;
use App\Uploads\UploadService;

$destino = sys_get_temp_dir() . '/aula-anexos-' . bin2hex(random_bytes(3));
mkdir($destino, 0o775, true);
$service = new UploadService($destino);

/** @return array{tmp_name: string, error: int, size: int, name: string} */
$arquivo = static function (string $conteudo, string $nome, int $erro = UPLOAD_ERR_OK): array {
    $tmp = (string) tempnam(sys_get_temp_dir(), 'up');
    file_put_contents($tmp, $conteudo);

    return ['tmp_name' => $tmp, 'error' => $erro, 'size' => strlen($conteudo), 'name' => $nome];
};

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

titulo('Aula 1 — Upload seguro de anexos');

secao('As três coisas do cliente em que NÃO se pode confiar');

foreach (['nome do arquivo', 'extensão', 'Content-Type declarado'] as $item) {
    echo "  · {$item}\n";
}
nota('Todos vêm do navegador. Todos são forjáveis.');

secao('Envio legítimo');

$ok = $service->armazenar($arquivo($png, 'contrato.png'), verificarUpload: false);

checa('tipo real detectado pelo conteúdo', $ok->mimeReal === 'image/png', 'finfo lê o magic number');
checa('nome ORIGINAL é só metadado', $ok->nomeOriginal === 'contrato.png', '');
checa('no disco vai um nome NOVO', $ok->nomeArmazenado !== 'contrato.png', $ok->nomeArmazenado);
checa('nome gerado é hex aleatório + extensão', (bool) preg_match('/^[0-9a-f]{32}\.png$/', $ok->nomeArmazenado), '');
checa('o arquivo existe no destino', is_file($destino . '/' . $ok->nomeArmazenado), '');

secao('ATAQUE 1 — PHP renomeado para .jpg');

checaExcecao(
    'shell disfarçado de imagem é recusado',
    UploadInvalido::class,
    static fn () => $service->armazenar($arquivo('<?php system($_GET["c"]); ?>', 'inocente.jpg'), verificarUpload: false),
);
nota('A extensão dizia .jpg e o Content-Type diria image/jpeg. O finfo olhou os');
nota('primeiros bytes e viu text/x-php — é o conteúdo que decide.');

secao('ATAQUE 2 — path traversal no nome');

$traversal = $service->armazenar($arquivo($png, '../../public/shell.png'), verificarUpload: false);

checa('basename cortou o ../', $traversal->nomeOriginal === 'shell.png', $traversal->nomeOriginal);
checa('e o nome no disco nem usa o original', !str_contains($traversal->nomeArmazenado, 'shell'), '');
checa('nada escapou do diretório', is_file($destino . '/' . $traversal->nomeArmazenado), '');

secao('ATAQUE 3 — upload que falhou no meio');

checaExcecao(
    'UPLOAD_ERR_PARTIAL é recusado',
    UploadInvalido::class,
    static fn () => $service->armazenar($arquivo($png, 'parcial.png', UPLOAD_ERR_PARTIAL), verificarUpload: false),
);
nota('Um upload interrompido ainda cria entrada em $_FILES. Sem checar o código');
nota('de erro, o sistema processa arquivo incompleto como se estivesse bom.');

secao('Limite de tamanho');

$grande = $arquivo($png, 'grande.png');
$grande['size'] = UploadService::TAMANHO_MAXIMO + 1;

checaExcecao('arquivo acima do limite é recusado', UploadInvalido::class, static fn () => $service->armazenar($grande, verificarUpload: false));
checa('limite é 10 MB', UploadService::TAMANHO_MAXIMO === 10485760, UploadService::TAMANHO_MAXIMO . ' bytes');

secao('Dois envios com o mesmo nome não se sobrescrevem');

$a = $service->armazenar($arquivo($png, 'foto.png'), verificarUpload: false);
$b = $service->armazenar($arquivo($png, 'foto.png'), verificarUpload: false);

checa('nomes armazenados diferentes', $a->nomeArmazenado !== $b->nomeArmazenado, 'random_bytes por envio');

secao('Onde o arquivo mora');

$fonte = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Support/Container.php');

checa('destino é storage/anexos', str_contains($fonte, '/storage/anexos'), 'FORA de public/');
checa('não existe pasta de anexo dentro de public/', !is_dir(__DIR__ . '/../crm-produto/public/storage'), '');
nota('Mesmo que a validação de tipo fosse burlada, um .php ali não é executável');
nota('por URL — o servidor web não serve esse diretório.');

// limpeza
foreach (glob($destino . '/*') ?: [] as $f) {
    unlink($f);
}
rmdir($destino);

fecharAula();
