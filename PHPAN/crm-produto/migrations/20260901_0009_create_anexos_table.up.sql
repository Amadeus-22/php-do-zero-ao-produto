CREATE TABLE anexos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_armazenado VARCHAR(64) NOT NULL,
    mime_real VARCHAR(100) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_anexos_armazenado (nome_armazenado),
    KEY idx_anexos_cliente (cliente_id),
    CONSTRAINT fk_anexos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
