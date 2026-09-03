CREATE TABLE contatos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    canal_preferido ENUM('email', 'telefone', 'whatsapp') NOT NULL DEFAULT 'email',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contatos_cliente (cliente_id),
    CONSTRAINT fk_contatos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
