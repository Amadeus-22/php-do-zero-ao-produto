CREATE TABLE planos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(32) NOT NULL,
    nome VARCHAR(80) NOT NULL,
    max_clientes INT UNSIGNED NOT NULL,
    max_usuarios INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_planos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
