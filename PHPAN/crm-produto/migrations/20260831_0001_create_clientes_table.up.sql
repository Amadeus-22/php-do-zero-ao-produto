CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    telefone VARCHAR(20) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deletado_em DATETIME NULL,
    UNIQUE KEY uq_clientes_email (email),
    KEY idx_clientes_ativo (deletado_em, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
