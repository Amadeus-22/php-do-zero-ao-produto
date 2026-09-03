<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

interface RepositorioDeClientes
{
    public function salvar(Cliente $cliente): Cliente;

    public function buscarPorId(int $id): ?Cliente;

    public function buscarPorEmail(string $email): ?Cliente;

    /** @return list<Cliente> */
    public function todos(): array;

    /** @return list<Cliente> */
    public function todosAtivos(): array;

    /** @return list<Cliente> */
    public function buscar(CriterioDeBusca $criterio): array;

    public function contar(CriterioDeBusca $criterio): int;

    public function remover(int $id): void;

    /** @return list<Cliente> Os que estão na lixeira (soft delete). */
    public function removidos(): array;

    public function restaurar(int $id): void;
}
