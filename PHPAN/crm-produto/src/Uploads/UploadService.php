<?php

declare(strict_types=1);

namespace App\Uploads;

use finfo;

/**
 * Upload é das superfícies mais exploradas de aplicação web, porque o cliente
 * controla três coisas em que se tende a confiar: nome, extensão e Content-Type.
 * Nenhuma das três é verificável — todas vêm do navegador.
 */
final readonly class UploadService
{
    public const TAMANHO_MAXIMO = 10 * 1024 * 1024;

    /** mime REAL => extensão que o servidor atribui */
    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function __construct(
        private string $diretorioDestino,
    ) {
    }

    /**
     * @param array{tmp_name: string, error: int, size: int, name: string} $arquivo
     * @param bool $verificarUpload false só em teste — is_uploaded_file() exige POST real
     * @throws UploadInvalido
     */
    public function armazenar(array $arquivo, bool $verificarUpload = true): UploadResultado
    {
        // Um upload interrompido ainda cria entrada em $_FILES: sem checar o
        // código de erro, o código processa arquivo incompleto como se fosse bom.
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            throw UploadInvalido::falhaNoEnvio($arquivo['error']);
        }

        if ($arquivo['size'] > self::TAMANHO_MAXIMO) {
            throw UploadInvalido::grandeDemais(self::TAMANHO_MAXIMO);
        }

        if ($verificarUpload && !is_uploaded_file($arquivo['tmp_name'])) {
            throw UploadInvalido::envioSuspeito();
        }

        // finfo lê os primeiros bytes (magic number) do arquivo REAL. Um .php
        // renomeado para .jpg continua sendo PHP por dentro, e é isso que ele vê.
        $mimeReal = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

        if (!is_string($mimeReal) || !isset(self::TIPOS_PERMITIDOS[$mimeReal])) {
            throw UploadInvalido::tipoNaoPermitido(is_string($mimeReal) ? $mimeReal : 'desconhecido');
        }

        // Nome NOVO: mata path traversal e colisão entre dois envios homônimos.
        $nomeArmazenado = bin2hex(random_bytes(16)) . '.' . self::TIPOS_PERMITIDOS[$mimeReal];
        $destino = rtrim($this->diretorioDestino, '/') . '/' . $nomeArmazenado;

        if (!is_dir($this->diretorioDestino)) {
            mkdir($this->diretorioDestino, 0o775, true);
        }

        $moveu = $verificarUpload
            ? move_uploaded_file($arquivo['tmp_name'], $destino)
            : rename($arquivo['tmp_name'], $destino);

        if (!$moveu) {
            throw UploadInvalido::envioSuspeito();
        }

        return new UploadResultado(
            // basename corta qualquer "../" que tenha vindo no nome original
            nomeOriginal: basename($arquivo['name']),
            nomeArmazenado: $nomeArmazenado,
            mimeReal: $mimeReal,
            tamanhoBytes: $arquivo['size'],
        );
    }
}
