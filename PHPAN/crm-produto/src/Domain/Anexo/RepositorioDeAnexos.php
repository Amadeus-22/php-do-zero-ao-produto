<?php

declare(strict_types=1);

namespace App\Domain\Anexo;

/**
 * Contrato dos anexos.
 *
 * Existe porque o ClienteController estava montando `SELECT * FROM anexos`
 * direto — e a aula 3 do Módulo 3 ("controller fino, sem SQL") pegou. Controller
 * que sabe SQL é o hábito que aquele módulo inteiro existe para quebrar.
 */
interface RepositorioDeAnexos
{
    /** @return list<array<string, mixed>> */
    public function doCliente(int $clienteId): array;

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array;

    public function registrar(int $clienteId, string $nomeOriginal, string $nomeArmazenado, string $mimeReal, int $tamanhoBytes, ?int $criadoPor): int;
}
