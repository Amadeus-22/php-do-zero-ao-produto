-- A idempotência de verdade é o UNIQUE: quem garante que o mesmo evento não é
-- processado duas vezes é o BANCO, não um if na aplicação.
CREATE TABLE eventos_webhook (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_externo_id VARCHAR(120) NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    processado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_eventos_externo (evento_externo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
