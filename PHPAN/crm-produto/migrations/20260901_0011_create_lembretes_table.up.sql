-- vence_em SEMPRE em UTC. Converter só na exibição.
CREATE TABLE lembretes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    vence_em DATETIME NOT NULL,
    status ENUM('pendente', 'notificado', 'concluido') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lembretes_status_vence (status, vence_em),
    CONSTRAINT fk_lembretes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_lembretes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
