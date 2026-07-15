-- Script para corrigir a estrutura das tabelas de votos (Urnas)

-- 1. Remove as tabelas antigas para recriá-las com a estrutura correta
-- ATENÇÃO: Isso apagará os votos de teste já realizados!
DROP TABLE IF EXISTS `ballots_officers_choices`;
DROP TABLE IF EXISTS `ballots_officers`;
DROP TABLE IF EXISTS `ballots_pastor`;

-- 2. Recria a tabela de votos para Pastor
CREATE TABLE `ballots_pastor` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `choice` ENUM('SIM', 'NAO', 'BRANCO') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bp_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bp_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Recria a tabela de votos para Oficiais/Sociedades/Diretoria
CREATE TABLE `ballots_officers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `election_id` INT NOT NULL,
    `scrutiny_id` INT NOT NULL,
    `ballot_token` VARCHAR(64) NOT NULL,
    `is_white` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bo_election` FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bo_scrutiny` FOREIGN KEY (`scrutiny_id`) REFERENCES `scrutiniums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cria a nova tabela que armazena os candidatos escolhidos em cada voto
CREATE TABLE `ballots_officers_choices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ballot_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    CONSTRAINT `fk_boc_ballot` FOREIGN KEY (`ballot_id`) REFERENCES `ballots_officers`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_boc_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;