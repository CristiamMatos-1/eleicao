CREATE TABLE IF NOT EXISTS `churches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default church to associate existing records
INSERT INTO `churches` (`name`, `slug`) VALUES ('Igreja Principal', 'igreja-principal');

-- Alter users table
ALTER TABLE `users` ADD COLUMN `church_id` INT DEFAULT NULL AFTER `id`;
UPDATE `users` SET `church_id` = (SELECT id FROM churches LIMIT 1) WHERE `church_id` IS NULL;
ALTER TABLE `users` MODIFY COLUMN `church_id` INT NOT NULL;
ALTER TABLE `users` ADD CONSTRAINT `fk_users_church` FOREIGN KEY (`church_id`) REFERENCES `churches`(`id`) ON DELETE CASCADE;

-- Allow CPF to be duplicated across different churches, but unique per church
-- Assuming there's a unique constraint on cpf, we drop it and create a composite one
-- (You might need to adjust the index name 'cpf' if it's different)
ALTER TABLE `users` DROP INDEX `cpf`;
ALTER TABLE `users` ADD UNIQUE INDEX `idx_cpf_church` (`cpf`, `church_id`);

-- Add SUPER_ADMIN to roles
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('ADMIN', 'CONDUTOR', 'ELEITOR', 'SUPER_ADMIN') NOT NULL DEFAULT 'ELEITOR';

-- Alter elections table
ALTER TABLE `elections` ADD COLUMN `church_id` INT DEFAULT NULL AFTER `id`;
UPDATE `elections` SET `church_id` = (SELECT id FROM churches LIMIT 1) WHERE `church_id` IS NULL;
ALTER TABLE `elections` MODIFY COLUMN `church_id` INT NOT NULL;
ALTER TABLE `elections` ADD CONSTRAINT `fk_elections_church` FOREIGN KEY (`church_id`) REFERENCES `churches`(`id`) ON DELETE CASCADE;

