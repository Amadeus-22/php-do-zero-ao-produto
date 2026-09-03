<?php

declare(strict_types=1);

namespace App\Infrastructure\Cliente;

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\CriterioDeBusca;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Cliente\StatusCliente;
use PDO;
use PDOException;

/**
 * A ÚNICA classe do projeto que sabe que existe SQL para clientes.
 * Nada acima dela (domínio, service, controller) mudou para esta implementação
 * existir — é a promessa da interface, cobrada na prática.
 */
final readonly class RepositorioDeClientesPdo implements RepositorioDeClientes
{
    private const CAMPOS = 'id, nome, email, telefone, status, criado_em';

    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function salvar(Cliente $cliente): Cliente
    {
        $id = $cliente->id();

        try {
            if ($id === null) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO clientes (nome, email, telefone, status, criado_em)
                     VALUES (:nome, :email, :telefone, :status, :criado_em)',
                );
                $stmt->execute([
                    'nome' => $cliente->nome(),
                    'email' => $cliente->email(),
                    'telefone' => $cliente->telefone(),
                    'status' => $cliente->status()->value,
                    'criado_em' => $cliente->criadoEm()->format('Y-m-d H:i:s'),
                ]);

                $id = (int) $this->pdo->lastInsertId();
            } else {
                $stmt = $this->pdo->prepare(
                    'UPDATE clientes
                        SET nome = :nome, email = :email, telefone = :telefone, status = :status
                      WHERE id = :id',
                );
                $stmt->execute([
                    'id' => $id,
                    'nome' => $cliente->nome(),
                    'email' => $cliente->email(),
                    'telefone' => $cliente->telefone(),
                    'status' => $cliente->status()->value,
                ]);
            }
        } catch (PDOException $e) {
            // 1062 = chave duplicada. A infraestrutura TRADUZ o erro técnico do MySQL
            // em erro de domínio; o domínio não precisa saber que MySQL existe.
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new EmailJaCadastrado($cliente->email());
            }

            throw $e; // falha de infraestrutura genuína — deixa subir
        }

        return $this->buscarPorId($id) ?? throw new \RuntimeException('Cliente sumiu logo após ser salvo.');
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CAMPOS . ' FROM clientes WHERE id = :id AND deletado_em IS NULL',
        );
        $stmt->execute(['id' => $id]);
        $linha = $stmt->fetch();

        return $linha === false ? null : $this->hidratar($linha);
    }

    public function buscarPorEmail(string $email): ?Cliente
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CAMPOS . ' FROM clientes WHERE email = :email AND deletado_em IS NULL',
        );
        $stmt->execute(['email' => strtolower($email)]);
        $linha = $stmt->fetch();

        return $linha === false ? null : $this->hidratar($linha);
    }

    /** @return list<Cliente> */
    public function todos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT ' . self::CAMPOS . ' FROM clientes WHERE deletado_em IS NULL ORDER BY nome',
        );

        return array_map($this->hidratar(...), $stmt === false ? [] : $stmt->fetchAll());
    }

    /** @return list<Cliente> */
    public function todosAtivos(): array
    {
        $stmt = $this->pdo->query(
            "SELECT " . self::CAMPOS . " FROM clientes
              WHERE deletado_em IS NULL AND status = 'ativo' ORDER BY nome",
        );

        return array_map($this->hidratar(...), $stmt === false ? [] : $stmt->fetchAll());
    }

    /**
     * Paginação NO SQL (LIMIT/OFFSET), não em PHP depois de um SELECT *.
     *
     * @return list<Cliente>
     */
    public function buscar(CriterioDeBusca $criterio): array
    {
        [$where, $params] = $this->condicoes($criterio);

        // LIMIT/OFFSET não aceitam parâmetro nomeado em todo driver; por isso
        // são interpolados — mas só depois de passarem por (int), nunca crus.
        $sql = 'SELECT ' . self::CAMPOS . " FROM clientes {$where} ORDER BY nome"
            . sprintf(' LIMIT %d OFFSET %d', $criterio->perPage, $criterio->offset());

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map($this->hidratar(...), $stmt->fetchAll());
    }

    public function contar(CriterioDeBusca $criterio): int
    {
        [$where, $params] = $this->condicoes($criterio);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clientes {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** Soft delete (Módulo 6): o registro continua, o sistema deixa de vê-lo. */
    public function remover(int $id): void
    {
        $this->pdo->prepare('UPDATE clientes SET deletado_em = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** @return list<Cliente> */
    public function removidos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT ' . self::CAMPOS . ' FROM clientes WHERE deletado_em IS NOT NULL ORDER BY deletado_em DESC',
        );

        return array_map($this->hidratar(...), $stmt === false ? [] : $stmt->fetchAll());
    }

    public function restaurar(int $id): void
    {
        $this->pdo->prepare('UPDATE clientes SET deletado_em = NULL WHERE id = :id')->execute(['id' => $id]);
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function condicoes(CriterioDeBusca $criterio): array
    {
        $where = ['deletado_em IS NULL'];
        $params = [];

        if ($criterio->q !== null && $criterio->q !== '') {
            // DOIS placeholders para o mesmo valor, de propósito: com
            // ATTR_EMULATE_PREPARES => false quem prepara é o MySQL, e ele NÃO
            // aceita o mesmo parâmetro nomeado repetido na query
            // ("Invalid parameter number"). Com emulação ligada isso passaria —
            // e quebraria só em produção.
            $where[] = '(nome LIKE :q_nome OR email LIKE :q_email)';
            $params['q_nome'] = '%' . $criterio->q . '%';
            $params['q_email'] = '%' . $criterio->q . '%';
        }

        if ($criterio->ativo !== null) {
            $where[] = 'status = :status';
            $params['status'] = $criterio->ativo ? 'ativo' : 'inativo';
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    /** @param array<string, mixed> $linha */
    private function hidratar(array $linha): Cliente
    {
        return Cliente::reconstituir(
            id: (int) $linha['id'],
            nome: (string) $linha['nome'],
            email: (string) $linha['email'],
            status: StatusCliente::from((string) $linha['status']),
            criadoEm: new \DateTimeImmutable((string) $linha['criado_em']),
            telefone: $linha['telefone'] === null ? null : (string) $linha['telefone'],
        );
    }
}
