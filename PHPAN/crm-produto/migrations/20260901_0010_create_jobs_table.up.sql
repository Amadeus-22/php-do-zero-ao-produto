CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(60) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pendente', 'processando', 'concluido', 'falhou') NOT NULL DEFAULT 'pendente',
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    disponivel_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    erro TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    concluido_em DATETIME NULL,
    KEY idx_jobs_status_disponivel (status, disponivel_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
