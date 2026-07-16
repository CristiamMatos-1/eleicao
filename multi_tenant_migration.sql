-- ========================================================
-- MIGRAÇÃO DE HARDENING MULTI-TENANT (SEM RESET DE DADOS)
-- Execute em bases já existentes
-- ========================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
START TRANSACTION;

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

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `role` ENUM('ADMIN', 'CONDUTOR', 'ELEITOR', 'SUPER_ADMIN') NOT NULL DEFAULT 'ELEITOR',
    ADD COLUMN IF NOT EXISTS `approved` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `active` TINYINT(1) NOT NULL DEFAULT 1;

UPDATE `users`
   SET `church_id` = 1
 WHERE `church_id` IS NULL;

ALTER TABLE `users`
    MODIFY COLUMN `church_id` INT NOT NULL;

ALTER TABLE `elections`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `blind_tally` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
    ADD COLUMN IF NOT EXISTS `tally_released_at` DATETIME NULL AFTER `blind_tally`;

UPDATE `elections`
   SET `church_id` = 1
 WHERE `church_id` IS NULL;

ALTER TABLE `elections`
    MODIFY COLUMN `church_id` INT NOT NULL;

ALTER TABLE `scrutiniums`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`,
    ADD INDEX `idx_scrutiniums_scope` (`church_id`, `election_id`, `status`);

UPDATE `scrutiniums` s
JOIN `elections` e ON e.id = s.election_id
   SET s.church_id = e.church_id
 WHERE s.church_id IS NULL;

ALTER TABLE `scrutiniums`
    MODIFY COLUMN `church_id` INT NOT NULL;

ALTER TABLE `vote_control`
    ADD COLUMN IF NOT EXISTS `election_id` INT NULL AFTER `scrutiny_id`,
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `election_id`;

UPDATE `vote_control` vc
JOIN `scrutiniums` s ON s.id = vc.scrutiny_id
JOIN `elections` e ON e.id = s.election_id
   SET vc.election_id = s.election_id,
       vc.church_id = e.church_id
 WHERE vc.election_id IS NULL OR vc.church_id IS NULL;

ALTER TABLE `vote_control`
    MODIFY COLUMN `election_id` INT NOT NULL,
    MODIFY COLUMN `church_id` INT NOT NULL,
    ADD UNIQUE INDEX `uq_vote_control_election_cpf` (`election_id`, `cpf_hash`),
    ADD INDEX `idx_vote_control_scope` (`church_id`, `election_id`, `scrutiny_id`);

ALTER TABLE `ballots_pastor`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`,
    ADD INDEX `idx_bp_scope` (`church_id`, `election_id`, `scrutiny_id`);

UPDATE `ballots_pastor` bp
JOIN `elections` e ON e.id = bp.election_id
   SET bp.church_id = e.church_id
 WHERE bp.church_id IS NULL;

ALTER TABLE `ballots_pastor`
    MODIFY COLUMN `church_id` INT NOT NULL;

ALTER TABLE `ballots_officers`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`,
    ADD INDEX `idx_bo_scope` (`church_id`, `election_id`, `scrutiny_id`);

UPDATE `ballots_officers` bo
JOIN `elections` e ON e.id = bo.election_id
   SET bo.church_id = e.church_id
 WHERE bo.church_id IS NULL;

ALTER TABLE `ballots_officers`
    MODIFY COLUMN `church_id` INT NOT NULL;

ALTER TABLE `elected_candidates`
    ADD COLUMN IF NOT EXISTS `church_id` INT NULL AFTER `id`;

UPDATE `elected_candidates` ec
JOIN `elections` e ON e.id = ec.election_id
   SET ec.church_id = e.church_id
 WHERE ec.church_id IS NULL;

ALTER TABLE `elected_candidates`
    MODIFY COLUMN `church_id` INT NOT NULL,
    ADD INDEX `idx_ec_scope` (`church_id`, `election_id`);

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
