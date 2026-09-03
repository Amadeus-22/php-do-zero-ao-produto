-- token_hash guarda SHA-256 do token, nunca o token em si: se o banco vazar,
-- os hashes não são reutilizáveis como credencial (mesma lógica de senha).
CREATE TABLE tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('access', 'refresh') NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tokens_hash (token_hash),
    KEY idx_tokens_usuario (usuario_id),
    CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
