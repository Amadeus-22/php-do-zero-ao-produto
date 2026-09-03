<?php

declare(strict_types=1);

namespace Tests\Uploads;

use App\Uploads\UploadInvalido;
use App\Uploads\UploadService;
use PHPUnit\Framework\TestCase;

final class UploadServiceTest extends TestCase
{
    private string $destino = '';

    protected function setUp(): void
    {
        $this->destino = sys_get_temp_dir() . '/crm-anexos-' . bin2hex(random_bytes(4));
        mkdir($this->destino, 0o775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->destino . '/*') ?: [] as $f) {
            unlink($f);
        }

        if (is_dir($this->destino)) {
            rmdir($this->destino);
        }
    }

    /** @return array{tmp_name: string, error: int, size: int, name: string} */
    private function arquivo(string $conteudo, string $nome): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $conteudo);

        return ['tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => strlen($conteudo), 'name' => $nome];
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }

    public function testAceitaTipoPermitidoEGeraNomeNovo(): void
    {
        $r = (new UploadService($this->destino))->armazenar($this->arquivo($this->png(), 'foto.png'), false);

        self::assertSame('image/png', $r->mimeReal);
        self::assertSame('foto.png', $r->nomeOriginal);
        self::assertNotSame('foto.png', $r->nomeArmazenado, 'o nome original não pode ir para o disco');
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}\.png$/', $r->nomeArmazenado);
        self::assertFileExists($this->destino . '/' . $r->nomeArmazenado);
    }

    /** O ataque central da aula: PHP disfarçado de imagem. */
    public function testRecusaPhpRenomeadoParaJpg(): void
    {
        $this->expectException(UploadInvalido::class);

        (new UploadService($this->destino))->armazenar(
            $this->arquivo('<?php system($_GET["c"]); ?>', 'inocente.jpg'),
            false,
        );
    }

    public function testNomeComPathTraversalNaoEscapaDoDiretorio(): void
    {
        $r = (new UploadService($this->destino))->armazenar(
            $this->arquivo($this->png(), '../../public/shell.png'),
            false,
        );

        self::assertSame('shell.png', $r->nomeOriginal, 'basename deveria cortar o ../');
        self::assertStringNotContainsString('..', $r->nomeArmazenado);
        self::assertFileExists($this->destino . '/' . $r->nomeArmazenado);
    }

    public function testRecusaArquivoMaiorQueOLimite(): void
    {
        $this->expectException(UploadInvalido::class);

        $arquivo = $this->arquivo($this->png(), 'grande.png');
        $arquivo['size'] = UploadService::TAMANHO_MAXIMO + 1;

        (new UploadService($this->destino))->armazenar($arquivo, false);
    }

    public function testRecusaUploadQueFalhouNoMeio(): void
    {
        $this->expectException(UploadInvalido::class);

        $arquivo = $this->arquivo($this->png(), 'parcial.png');
        $arquivo['error'] = UPLOAD_ERR_PARTIAL;

        (new UploadService($this->destino))->armazenar($arquivo, false);
    }

    public function testDoisEnviosComMesmoNomeNaoSeSobrescrevem(): void
    {
        $s = new UploadService($this->destino);
        $a = $s->armazenar($this->arquivo($this->png(), 'foto.png'), false);
        $b = $s->armazenar($this->arquivo($this->png(), 'foto.png'), false);

        self::assertNotSame($a->nomeArmazenado, $b->nomeArmazenado);
        self::assertCount(2, glob($this->destino . '/*') ?: []);
    }

    public function testDiretorioDeDestinoFicaForaDePublic(): void
    {
        $raiz = dirname(__DIR__, 2);
        $reflexo = new \ReflectionClass(\App\Support\Container::class);
        $fonte = (string) file_get_contents((string) $reflexo->getFileName());

        self::assertStringContainsString("/storage/anexos", $fonte);
        self::assertDirectoryDoesNotExist($raiz . '/public/storage', 'anexo não pode ser servido por URL direta');
    }
}
