<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

/**
 * Autorização num lugar só.
 *
 * Autenticação responde "quem é você"; autorização responde "o que você pode
 * fazer". Espalhar `if ($papel === 'admin')` pelos controllers funciona no começo
 * e vira inconsistência: um endpoint esquece de checar, outro checa errado. Com a
 * tabela aqui, "quem pode o quê" se audita lendo um arquivo.
 */
final class Gate
{
    /** @var array<string, list<Papel>> */
    private const REGRAS = [
        'cliente.listar' => [Papel::ADMIN, Papel::VENDEDOR, Papel::LEITURA],
        'cliente.ver' => [Papel::ADMIN, Papel::VENDEDOR, Papel::LEITURA],
        'cliente.criar' => [Papel::ADMIN, Papel::VENDEDOR],
        'cliente.editar' => [Papel::ADMIN, Papel::VENDEDOR],
        'cliente.excluir' => [Papel::ADMIN],
        'cliente.restaurar' => [Papel::ADMIN],
        'cliente.exportar' => [Papel::ADMIN, Papel::VENDEDOR],
        'usuario.gerenciar' => [Papel::ADMIN],
        'auditoria.ver' => [Papel::ADMIN],
    ];

    public function pode(Papel $papel, string $acao): bool
    {
        return in_array($papel, self::REGRAS[$acao] ?? [], strict: true);
    }

    /** @return list<string> Toda ação conhecida — usado para auditar a matriz. */
    public static function acoes(): array
    {
        return array_keys(self::REGRAS);
    }

    /** @return list<string> As ações que o papel pode executar. */
    public function acoesDe(Papel $papel): array
    {
        return array_values(array_filter(self::acoes(), fn (string $a): bool => $this->pode($papel, $a)));
    }
}
