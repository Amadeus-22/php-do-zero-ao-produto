<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Cliente\Cliente;

/**
 * Controla EXATAMENTE o que sai no JSON. Serializar a entidade direto faria
 * o DateTimeImmutable virar {"date":...,"timezone_type":3,...} e, pior, qualquer
 * campo interno futuro vazaria no contrato sem ninguém perceber.
 */
final class ClienteResource
{
    /** @return array{id:?int, nome:string, email:string, telefone:?string, ativo:bool, criado_em:string} */
    public static function um(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id(),
            'nome' => $cliente->nome(),
            'email' => $cliente->email(),
            'telefone' => $cliente->telefone(),
            'ativo' => $cliente->estaAtivo(),
            'criado_em' => $cliente->criadoEm()->format(DATE_ATOM),
        ];
    }

    /**
     * @param list<Cliente> $clientes
     * @return list<array{id:?int, nome:string, email:string, telefone:?string, ativo:bool, criado_em:string}>
     */
    public static function colecao(array $clientes): array
    {
        return array_map(self::um(...), $clientes);
    }
}
