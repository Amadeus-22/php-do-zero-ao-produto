-- Mini CRM — estrutura do banco
-- Uso: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS mini_crm
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE mini_crm;

CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(120) NOT NULL,
  email      VARCHAR(180) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contatos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id   INT UNSIGNED NOT NULL,
  nome      VARCHAR(120) NOT NULL,
  email     VARCHAR(180) NULL,
  telefone  VARCHAR(30)  NULL,
  notas     TEXT         NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_contatos_user_nome (user_id, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
