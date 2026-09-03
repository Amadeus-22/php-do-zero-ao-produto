-- APPEND-ONLY: sem coluna de atualização, e nenhum UPDATE/DELETE no código.
-- usuario_id é NULL para ação do sistema (job, cron).
CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    acao VARCHAR(60) NOT NULL,
    entidade VARCHAR(60) NOT NULL,
    entidade_id INT UNSIGNED NOT NULL,
    dados_antes JSON NULL,
    dados_depois JSON NULL,
    ip VARCHAR(45) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_entidade (entidade, entidade_id),
    KEY idx_auditoria_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
