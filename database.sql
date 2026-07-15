-- ========================================================
-- SISTEMA DE ELEIÇÃO ECLESIÁSTICA - SCRIPT DE INSTALAÇÃO
-- Versão com Multi-Tenant (Múltiplas Igrejas) e Sociedades
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. TABELA DE IGREJAS (MULTI-TENANT)
CREATE TABLE IF NOT EXISTS `churches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cria a primeira Igreja (Igreja Padrão)
INSERT INTO `churches` (`id`, `name`, `slug`) VALUES (1, 'Igreja Sede', 'igreja-sede');

-- 2. TABELA DE CONFIGURAÇÕES GERAIS
CREATE TABLE IF NOT EXISTS `registration_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_open` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere o registro de configuração
INSERT INTO `registration_settings` (`id`, `registration_open`) VALUES (1, 0);

-- 3. TABELA DE USUÁRIOS
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `role` ENUM('ADMIN', 'CONDUTOR', 'ELEITOR', 'SUPER_ADMIN') NOT NULL DEFAULT 'ELEITOR',
    `name` VARCHAR(160) NOT NULL,
    `cpf` VARCHAR(14) DEFAULT NULL,
    `email` VARCHAR(120) DEFAULT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `approved` TINYINT(1) NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_church` FOREIGN KEY (`church_id`) REFERENCES `churches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cria o Super Admin Padrão (E-mail: superadmin@admin.com / Senha: 123456)
INSERT INTO `users` (`church_id`, `role`, `name`, `email`, `password_hash`, `approved`, `active`) 
VALUES (1, 'SUPER_ADMIN', 'Super Administrador', 'superadmin@admin.com', '$2y$12$9W4T8Miwvk3mr2y99QMYmuGn4a1aPCyjmMGkqxctP5MFwKbe8sHKi', 1, 1);

-- Cria índice composto para CPF único por igreja (permite mesmo CPF em igrejas diferentes)
ALTER TABLE `users` ADD UNIQUE INDEX `idx_cpf_church` (`cpf`, `church_id`);
-- Email único geral para acesso administrativo
ALTER TABLE `users` ADD UNIQUE INDEX `idx_email` (`email`);

-- 4. TABELA DE ELEIÇÕES
CREATE TABLE IF NOT EXISTS `elections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `type` ENUM('PASTOR', 'OFICIAIS', 'DIRETORIA', 'SOCIEDADES') NOT NULL,
    `title` VARCHAR(190) NOT NULL,
    `election_date` DATE NOT NULL,
    `expected_voters` INT NOT NULL,
    `vacancies` INT DEFAULT NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `public_key` VARCHAR(36) NOT NULL UNIQUE,
    `cpf_salt` BINARY(16) NOT NULL,
    `opened_at` DATETIME NOT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_elections_church` FOREIGN KEY (`church_id`) REFERENCES `churches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE ELEITORES CREDENCIADOS (APENAS PARA DIRETORIA)
CREATE TABLE IF NOT EXISTS `election_voters` (
    `election_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`election_id`, `user_id`),
    CONSTRAINT `fk_ev_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA DE CANDIDATOS
CREATE TABLE IF NOT EXISTS `candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `photo_path` VARCHAR(255) DEFAULT NULL,
    `role_title` VARCHAR(120) DEFAULT NULL,
    `pastor_term_years` INT DEFAULT NULL,
    `status` ENUM('ACTIVE', 'ELIMINATED', 'ELECTED') NOT NULL DEFAULT 'ACTIVE',
    CONSTRAINT `fk_candidates_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA DE ESCRUTÍNIOS
CREATE TABLE IF NOT EXISTS `scrutiniums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `number` INT NOT NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `expected_voters` INT NOT NULL,
    `vote_count` INT NOT NULL DEFAULT 0,
    `opened_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_scrutiniums_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_scrutiny_number` (`election_id`, `number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. CONTROLE DE VOTOS (ANONIMATO COM HASH HMAC)
CREATE TABLE IF NOT EXISTS `vote_control` (
    `scrutiny_id` INT NOT NULL,
    `cpf_hash` CHAR(64) NOT NULL,
    `voted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`scrutiny_id`, `cpf_hash`),
    CONSTRAINT `fk_vc_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. URNAS (VOTOS DE PASTOR)
CREATE TABLE IF NOT EXISTS `ballots_pastor` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `choice` ENUM('SIM', 'NAO', 'BRANCO') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bp_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bp_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. URNAS (VOTOS DE OFICIAIS, DIRETORIA E SOCIEDADES)
CREATE TABLE IF NOT EXISTS `ballots_officers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `is_white` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bo_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bo_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.1 ESCOLHAS DE CANDIDATOS NAS URNAS DE OFICIAIS
CREATE TABLE IF NOT EXISTS `ballots_officers_choices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ballot_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    CONSTRAINT `fk_boc_ballot` FOREIGN KEY (`ballot_id`) REFERENCES `ballots_officers`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_boc_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. CANDIDATOS ELEITOS E REGRAS DE VITÓRIA
CREATE TABLE IF NOT EXISTS `elected_candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    `elected_in_scrutiny` INT NOT NULL,
    `rule` VARCHAR(50) NOT NULL,
    `votes` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ec_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ec_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_elected_candidate` (`election_id`, `candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
