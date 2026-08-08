START TRANSACTION;

-- 1) churches.legal_name: Nome completo da Entidade / Condomínio (Razão Social)
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'churches'
      AND COLUMN_NAME  = 'legal_name'
);
SET @sql = IF(@col_exists = 0,
    "ALTER TABLE churches ADD COLUMN legal_name VARCHAR(255) NULL COMMENT 'Nome completo da Entidade / Condomínio (Razão Social para PDF)' AFTER name",
    "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE churches
   SET legal_name = name
 WHERE legal_name IS NULL OR TRIM(legal_name) = '';

-- 2) elections.assembly_type: Natureza da Assembleia Geral
SET @col_exists2 = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'elections'
      AND COLUMN_NAME  = 'assembly_type'
);
SET @sql2 = IF(@col_exists2 = 0,
    "ALTER TABLE elections ADD COLUMN assembly_type ENUM('ORDINARIA','EXTRAORDINARIA') NOT NULL DEFAULT 'ORDINARIA' COMMENT 'Natureza da Assembleia Geral (Ordinária ou Extraordinária)' AFTER type",
    "SELECT 1"
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

COMMIT;
