CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    papel ENUM('admin', 'vendedor', 'leitura') NOT NULL DEFAULT 'leitura',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
