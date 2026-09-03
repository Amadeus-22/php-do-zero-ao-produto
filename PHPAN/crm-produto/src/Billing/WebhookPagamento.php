<?php

declare(strict_types=1);

namespace App\Billing;

use App\Log\Logger;
use PDO;
use Throwable;

/**
 * Endpoint de webhook é uma URL PÚBLICA.
 *
 * Sem verificação de assinatura, qualquer um manda "pagamento aprovado" e libera
 * acesso de graça. E gateways REENVIAM eventos — sem idempotência, o mesmo
 * pagamento é processado várias vezes.
 */
final readonly class WebhookPagamento
{
    public function __construct(
        private PDO $pdo,
        private AssinaturaService $assinaturas,
        private string $chaveSecreta,
        private Logger $logger,
    ) {
    }

    /** @return array{status: int, body: array<string, mixed>} */
    public function processar(string $payloadCru, string $assinaturaRecebida): array
    {
        if (!$this->assinaturaValida($payloadCru, $assinaturaRecebida)) {
            $this->logger->warning('webhook com assinatura inválida');

            return ['status' => 401, 'body' => ['erro' => 'assinatura_invalida']];
        }

        try {
            $evento = json_decode($payloadCru, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ['status' => 400, 'body' => ['erro' => 'payload_invalido']];
        }

        $eventoId = is_array($evento) ? ($evento['id'] ?? null) : null;

        if (!is_string($eventoId) || !isset($evento['type'])) {
            return ['status' => 400, 'body' => ['erro' => 'payload_invalido']];
        }

        if ($this->jaProcessado($eventoId)) {
            // 200, não erro: para o gateway isto foi entregue com sucesso
            return ['status' => 200, 'body' => ['status' => 'ja_processado']];
        }

        $this->pdo->beginTransaction();

        try {
            // O INSERT vai na MESMA transação da mudança de assinatura: senão dá
            // para ter evento marcado como processado sem a assinatura ter mudado.
            $this->registrar($eventoId, (string) $evento['type']);

            match ($evento['type']) {
                'payment.succeeded' => $this->assinaturas->ativar((int) $evento['data']['assinatura_id']),
                'payment.failed' => $this->assinaturas->marcarAtrasada((int) $evento['data']['assinatura_id']),
                'subscription.canceled' => $this->assinaturas->cancelar((int) $evento['data']['assinatura_id']),
                default => null, // evento que não nos interessa: ignora, não quebra
            };

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            $this->logger->error('falha ao processar webhook', ['evento' => $eventoId, 'erro' => $e->getMessage()]);

            // 5xx faz o gateway reenviar — que é o que queremos numa falha nossa
            return ['status' => 500, 'body' => ['erro' => 'falha_interna']];
        }

        return ['status' => 200, 'body' => ['status' => 'processado']];
    }

    private function assinaturaValida(string $payload, string $recebida): bool
    {
        // hash_equals: comparação em tempo constante, não vaza por timing
        return hash_equals(hash_hmac('sha256', $payload, $this->chaveSecreta), $recebida);
    }

    private function jaProcessado(string $eventoId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM eventos_webhook WHERE evento_externo_id = :id');
        $stmt->execute(['id' => $eventoId]);

        return $stmt->fetchColumn() !== false;
    }

    private function registrar(string $eventoId, string $tipo): void
    {
        $this->pdo->prepare('INSERT INTO eventos_webhook (evento_externo_id, tipo) VALUES (:id, :tipo)')
            ->execute(['id' => $eventoId, 'tipo' => $tipo]);
    }
}
