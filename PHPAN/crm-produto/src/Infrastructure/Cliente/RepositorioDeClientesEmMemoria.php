<?php

declare(strict_types=1);

namespace App\Infrastructure\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\RepositorioDeClientes;

/**
 * Implementação em memória: usada nos testes de unidade.
 * Roda em milissegundos e não depende de banco nem de estado externo.
 */
final class RepositorioDeClientesEmMemoria implements RepositorioDeClientes
{
    /** @var array<int, Cliente> */
    private array $clientes = [];

    /** @var array<int, Cliente> lixeira: soft delete não joga o dado fora */
    private array $removidos = [];

    private int $proximoId = 1;

    public function salvar(Cliente $cliente): Cliente
    {
        $id = $cliente->id();

        if ($id !== null) {
            $this->clientes[$id] = $cliente;

            return $cliente;
        }

        $novoId = $this->proximoId++;
        $salvo = Cliente::reconstituir(
            id: $novoId,
            nome: $cliente->nome(),
            email: $cliente->email(),
            status: $cliente->status(),
            criadoEm: $cliente->criadoEm(),
            telefone: $cliente->telefone(),
        );
        $this->clientes[$novoId] = $salvo;

        return $salvo;
    }

    public function buscarPorId(int $id): ?Cliente
    {
        return $this->clientes[$id] ?? null;
    }

    public function buscarPorEmail(string $email): ?Cliente
    {
        foreach ($this->clientes as $cliente) {
            if (strtolower($cliente->email()) === strtolower($email)) {
                return $cliente;
            }
        }

        return null;
    }

    /** @return list<Cliente> */
    public function todos(): array
    {
        return array_values($this->clientes);
    }

    public function remover(int $id): void
    {
        if (isset($this->clientes[$id])) {
            $this->removidos[$id] = $this->clientes[$id];
            unset($this->clientes[$id]);
        }
    }

    /** @return list<Cliente> */
    public function removidos(): array
    {
        return array_values($this->removidos);
    }

    public function restaurar(int $id): void
    {
        if (isset($this->removidos[$id])) {
            $this->clientes[$id] = $this->removidos[$id];
            unset($this->removidos[$id]);
        }
    }

    /** @return list<Cliente> */
    public function todosAtivos(): array
    {
        return array_values(array_filter(
            $this->clientes,
            static fn (Cliente $c): bool => $c->estaAtivo(),
        ));
    }

    /** @return list<Cliente> */
    public function buscar(\App\Domain\Cliente\CriterioDeBusca $criterio): array
    {
        return array_slice($this->filtrar($criterio), $criterio->offset(), $criterio->perPage);
    }

    public function contar(\App\Domain\Cliente\CriterioDeBusca $criterio): int
    {
        return count($this->filtrar($criterio));
    }

    /**
     * NOTA: filtrar em PHP só é aceitável porque a fonte é um arquivo pequeno.
     * Num repositório PDO isto DEVE virar WHERE + LIMIT/OFFSET no SQL — paginar
     * depois de um SELECT * é a armadilha que a aula 4 do Módulo 4 aponta.
     *
     * @return list<Cliente>
     */
    private function filtrar(\App\Domain\Cliente\CriterioDeBusca $criterio): array
    {
        $clientes = $this->todos();

        if ($criterio->ativo !== null) {
            $clientes = array_filter($clientes, static fn (Cliente $c): bool => $c->estaAtivo() === $criterio->ativo);
        }

        if ($criterio->q !== null && $criterio->q !== '') {
            $termo = mb_strtolower($criterio->q);
            $clientes = array_filter($clientes, static fn (Cliente $c): bool
                => str_contains(mb_strtolower($c->nome()), $termo)
                || str_contains(mb_strtolower($c->email()), $termo));
        }

        return array_values($clientes);
    }
}
