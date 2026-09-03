CREATE TABLE assinaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conta_id INT UNSIGNED NOT NULL,
    plano_id INT UNSIGNED NOT NULL,
    status ENUM('ativa', 'atrasada', 'cancelada') NOT NULL DEFAULT 'ativa',
    renova_em DATE NOT NULL,
    atrasada_desde DATE NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_assinaturas_conta (conta_id, status),
    CONSTRAINT fk_assinaturas_plano FOREIGN KEY (plano_id) REFERENCES planos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
