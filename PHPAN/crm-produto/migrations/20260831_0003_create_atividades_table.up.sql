CREATE TABLE atividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    tipo ENUM('ligacao', 'email', 'reuniao', 'nota') NOT NULL,
    descricao TEXT NOT NULL,
    ocorrida_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_atividades_cliente (cliente_id, ocorrida_em),
    CONSTRAINT fk_atividades_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
