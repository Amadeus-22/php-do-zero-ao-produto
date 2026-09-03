-- chave combina identificador + IP: só IP pune escritório inteiro atrás de NAT;
-- só identificador deixa o atacante rodar em paralelo contra vários e-mails.
CREATE TABLE tentativas_login (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(191) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tentativas_chave_data (chave, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
