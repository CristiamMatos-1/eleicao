-- ========================================================
-- SISTEMA DE ELEIÇÃO ECLESIÁSTICA - SCHEMA COMPLETO
-- Multi-Tenant + Integridade + Concorrência
-- Compatível com MySQL 8+ e MariaDB 10.3+
-- ========================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
START TRANSACTION;

-- 1) TENANTS (IGREJAS / EMPRESAS)
CREATE TABLE IF NOT EXISTS `churches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_churches_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `churches` (`id`, `name`, `slug`)
VALUES (1, 'Igreja Sede', 'igreja-sede')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2) CONFIGURAÇÕES GLOBAIS DE CADASTRO
CREATE TABLE IF NOT EXISTS `registration_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_open` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `registration_settings` (`id`, `registration_open`)
VALUES (1, 0)
ON DUPLICATE KEY UPDATE `registration_open` = `registration_open`;

-- 3) USUÁRIOS (ADMINS + ELEITORES + SUPER_ADMIN)
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
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_cpf_church` (`cpf`, `church_id`),
    UNIQUE KEY `idx_email` (`email`),
    KEY `idx_users_church_role_active` (`church_id`, `role`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Super Admin padrão (trocar senha no primeiro login)
INSERT INTO `users` (`church_id`, `role`, `name`, `email`, `password_hash`, `approved`, `active`)
VALUES (1, 'SUPER_ADMIN', 'Super Administrador', 'superadmin@admin.com', '$2y$12$9W4T8Miwvk3mr2y99QMYmuGn4a1aPCyjmMGkqxctP5MFwKbe8sHKi', 1, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 4) ELEIÇÕES
CREATE TABLE IF NOT EXISTS `elections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `type` ENUM('PASTOR', 'OFICIAIS', 'DIRETORIA', 'SOCIEDADES') NOT NULL,
    `title` VARCHAR(190) NOT NULL,
    `election_date` DATE NOT NULL,
    `expected_voters` INT NOT NULL,
    `vacancies` INT DEFAULT NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `blind_tally` TINYINT(1) NOT NULL DEFAULT 0,
    `tally_released_at` DATETIME DEFAULT NULL,
    `public_key` VARCHAR(36) NOT NULL,
    `cpf_salt` BINARY(16) NOT NULL,
    `opened_at` DATETIME NOT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_elections_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_elections_public_key` (`public_key`),
    KEY `idx_elections_church_status` (`church_id`, `status`),
    KEY `idx_elections_period` (`opened_at`, `closed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) ELEITORES CREDENCIADOS POR ELEIÇÃO (DIRETORIA)
CREATE TABLE IF NOT EXISTS `election_voters` (
    `election_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`election_id`, `user_id`),
    CONSTRAINT `fk_ev_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) CANDIDATOS
CREATE TABLE IF NOT EXISTS `candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `photo_path` VARCHAR(255) DEFAULT NULL,
    `role_title` VARCHAR(120) DEFAULT NULL,
    `pastor_term_years` INT DEFAULT NULL,
    `status` ENUM('ACTIVE', 'ELIMINATED', 'ELECTED') NOT NULL DEFAULT 'ACTIVE',
    CONSTRAINT `fk_candidates_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    KEY `idx_candidates_election_status` (`election_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) ESCRUTÍNIOS
CREATE TABLE IF NOT EXISTS `scrutiniums` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `election_id` INT NOT NULL,
    `number` INT NOT NULL,
    `status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `expected_voters` INT NOT NULL,
    `vote_count` INT NOT NULL DEFAULT 0,
    `opened_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_scrutiniums_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_scrutiniums_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_scrutiny_number` (`election_id`, `number`),
    KEY `idx_scrutiniums_scope` (`church_id`, `election_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) CONTROLE DE VOTO (ANÔNIMO COM HASH)
-- Regra crítica de concorrência:
--   UNIQUE (election_id, cpf_hash) bloqueia voto duplicado no mesmo pleito.
CREATE TABLE IF NOT EXISTS `vote_control` (
    `scrutiny_id` INT NOT NULL,
    `election_id` INT NOT NULL,
    `church_id` INT NOT NULL,
    `cpf_hash` CHAR(64) NOT NULL,
    `voted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`scrutiny_id`, `cpf_hash`),
    UNIQUE KEY `uq_vote_control_election_cpf` (`election_id`, `cpf_hash`),
    KEY `idx_vote_control_scope` (`church_id`, `election_id`, `scrutiny_id`),
    CONSTRAINT `fk_vc_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vc_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vc_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) URNA PASTOR
CREATE TABLE IF NOT EXISTS `ballots_pastor` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `choice` ENUM('SIM', 'NAO', 'BRANCO') NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bp_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bp_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bp_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums` (`id`) ON DELETE CASCADE,
    KEY `idx_bp_scope` (`church_id`, `election_id`, `scrutiny_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) URNA OFICIAIS/DIRETORIA/SOCIEDADES
CREATE TABLE IF NOT EXISTS `ballots_officers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `is_white` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bo_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bo_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bo_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums` (`id`) ON DELETE CASCADE,
    KEY `idx_bo_scope` (`church_id`, `election_id`, `scrutiny_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.1) ESCOLHAS DE CANDIDATOS
CREATE TABLE IF NOT EXISTS `ballots_officers_choices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ballot_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    CONSTRAINT `fk_boc_ballot` FOREIGN KEY (`ballot_id`) REFERENCES `ballots_officers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_boc_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_ballot_candidate` (`ballot_id`, `candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) RESULTADO FINAL
CREATE TABLE IF NOT EXISTS `elected_candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `election_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    `elected_in_scrutiny` INT NOT NULL,
    `rule` VARCHAR(50) NOT NULL,
    `votes` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ec_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ec_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ec_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `idx_elected_candidate` (`election_id`, `candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12) OUTBOX PARA EVENTOS (Realtime Dashboard via SSE/WebSocket)
CREATE TABLE IF NOT EXISTS `outbox_events` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `aggregate_type` VARCHAR(50) NOT NULL,
    `aggregate_id` INT NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `payload` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_outbox_church` FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    KEY `idx_outbox_pending` (`processed_at`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13) AUDITORIA DE ROTEAMENTO DE LOGIN ÚNICO
CREATE TABLE IF NOT EXISTS `login_routing_audit` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `document_hash` CHAR(64) NOT NULL,
    `resolved_role` ENUM('SUPER_ADMIN', 'ADMIN', 'ELEITOR', 'UNKNOWN') NOT NULL,
    `church_id` INT DEFAULT NULL,
    `resolved_election_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_lra_created_role` (`created_at`, `resolved_role`),
    KEY `idx_lra_church` (`church_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================
-- TRIGGERS PARA PROPAGAR ISOLAMENTO DO TENANT
-- ===========================================
DROP TRIGGER IF EXISTS `trg_scrutiniums_set_church`;
DROP TRIGGER IF EXISTS `trg_vote_control_scope`;
DROP TRIGGER IF EXISTS `trg_ballots_pastor_set_church`;
DROP TRIGGER IF EXISTS `trg_ballots_officers_set_church`;
DROP TRIGGER IF EXISTS `trg_elected_candidates_set_church`;

DELIMITER $$

CREATE TRIGGER `trg_scrutiniums_set_church`
BEFORE INSERT ON `scrutiniums`
FOR EACH ROW
BEGIN
    IF NEW.church_id IS NULL OR NEW.church_id = 0 THEN
        SET NEW.church_id = (SELECT e.church_id FROM elections e WHERE e.id = NEW.election_id LIMIT 1);
    END IF;
END$$

CREATE TRIGGER `trg_vote_control_scope`
BEFORE INSERT ON `vote_control`
FOR EACH ROW
BEGIN
    DECLARE v_election_id INT;
    DECLARE v_church_id INT;

    SELECT s.election_id, s.church_id
      INTO v_election_id, v_church_id
      FROM scrutiniums s
     WHERE s.id = NEW.scrutiny_id
     LIMIT 1;

    SET NEW.election_id = v_election_id;
    SET NEW.church_id = v_church_id;
END$$

CREATE TRIGGER `trg_ballots_pastor_set_church`
BEFORE INSERT ON `ballots_pastor`
FOR EACH ROW
BEGIN
    IF NEW.church_id IS NULL OR NEW.church_id = 0 THEN
        SET NEW.church_id = (SELECT e.church_id FROM elections e WHERE e.id = NEW.election_id LIMIT 1);
    END IF;
END$$

CREATE TRIGGER `trg_ballots_officers_set_church`
BEFORE INSERT ON `ballots_officers`
FOR EACH ROW
BEGIN
    IF NEW.church_id IS NULL OR NEW.church_id = 0 THEN
        SET NEW.church_id = (SELECT e.church_id FROM elections e WHERE e.id = NEW.election_id LIMIT 1);
    END IF;
END$$

CREATE TRIGGER `trg_elected_candidates_set_church`
BEFORE INSERT ON `elected_candidates`
FOR EACH ROW
BEGIN
    IF NEW.church_id IS NULL OR NEW.church_id = 0 THEN
        SET NEW.church_id = (SELECT e.church_id FROM elections e WHERE e.id = NEW.election_id LIMIT 1);
    END IF;
END$$

DELIMITER ;

COMMIT;
