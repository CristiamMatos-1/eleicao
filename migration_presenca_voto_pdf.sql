-- ========================================================
-- MIGRAÇÃO: Registro de Presença + Status Workflow + PDF
-- Compatível com MySQL 8+ e MariaDB 10.3+
-- ========================================================
-- Este script é seguro para rodar em produção (idempotente).
-- Faz BACKUP do banco ANTES de executar.

START TRANSACTION;

-- --------------------------------------------------------
-- 1) ATUALIZAR ENUM DE STATUS DA TABELA elections
--    Mantém OPEN/CLOSED para compatibilidade com dados existentes
--    Novos status: aberta_para_presenca | aberta_para_votacao | encerrada
-- --------------------------------------------------------
-- Primeiro adiciona os NOVOS valores (ANTES dos antigos no enum,
-- para não quebrar os índices internos do MySQL).
ALTER TABLE elections
    MODIFY COLUMN status
    ENUM('aberta_para_presenca', 'aberta_para_votacao', 'encerrada', 'OPEN', 'CLOSED')
    NOT NULL
    DEFAULT 'aberta_para_presenca';

-- Migrar dados existentes para os NOVOS status:
--   OPEN    => aberta_para_votacao  (eleições abertas = prontas para votar)
--   CLOSED  => encerrada
UPDATE elections
   SET status = CASE
       WHEN status = 'OPEN'   THEN 'aberta_para_votacao'
       WHEN status = 'CLOSED' THEN 'encerrada'
       ELSE status
   END
 WHERE status IN ('OPEN', 'CLOSED');

-- Opcional: remover os valores antigos do ENUM se não houver mais nenhum uso.
-- Para manter compatibilidade máxima, deixamos os antigos no enum.
-- Se quiser "limpar" depois, descomente a linha abaixo (APENAS se o UPDATE acima converteu tudo):
-- ALTER TABLE elections MODIFY COLUMN status ENUM('aberta_para_presenca','aberta_para_votacao','encerrada') NOT NULL DEFAULT 'aberta_para_presenca';

-- --------------------------------------------------------
-- 2) CRIAR TABELA eleicao_presencas
--    Garante: um CPF = 1 presença por eleição (UNIQUE KEY).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eleicao_presencas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `church_id` INT NOT NULL,
    `eleicao_id` INT NOT NULL,
    `usuario_id` INT DEFAULT NULL,
    `cpf` VARCHAR(11) NOT NULL COMMENT 'CPF SOMENTE DÍGITOS (11 caracteres)',
    `nome` VARCHAR(160) NOT NULL,
    `data_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_presenca_church`
        FOREIGN KEY (`church_id`) REFERENCES `churches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_presenca_eleicao`
        FOREIGN KEY (`eleicao_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_presenca_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_presenca_eleicao_cpf` (`eleicao_id`, `cpf`),
    KEY `idx_presenca_church_eleicao` (`church_id`, `eleicao_id`),
    KEY `idx_presenca_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- ========================================================
-- 3) ADICIONAR NOVAS COLUNAS NA TABELA elections
--    Campos usados em CRIAR ASSEMBLEIA (Título da Entidade,
--    Natureza Ordinária/Extraordinária, Eleitores Esperados
--    e nomes herdados da igreja para cabeçalho do PDF).
--
--    Segurança: MySQL/MariaDB NÃO TEM "ADD COLUMN IF NOT EXISTS"
--    nativo. Usamos stored procedure temporária + INFORMATION_SCHEMA
--    para rodar ALTER TABLE apenas se a coluna ainda não existir.
--    O script é TOTALMENTE idempotente (seguro rodar múltiplas
--    vezes em produção sem quebrar nada).
-- ========================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `mig_add_col_if_not_exists`$$

CREATE PROCEDURE `mig_add_col_if_not_exists`(
    IN p_table   VARCHAR(64),
    IN p_column  VARCHAR(64),
    IN p_def     TEXT
)
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO col_exists
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = p_table
       AND COLUMN_NAME  = p_column;
    IF col_exists = 0 THEN
        SET @_mig_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_def);
        PREPARE stmt FROM @_mig_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- expected_voters: quantidade de eleitores esperados (usado p/ quórum e PDF)
CALL mig_add_col_if_not_exists('elections', 'expected_voters',
    'INT NOT NULL DEFAULT 0 COMMENT ''Eleitores esperados (quórum)''');

-- assembly_type: natureza da assembleia — ORDINARIA / EXTRAORDINARIA
CALL mig_add_col_if_not_exists('elections', 'assembly_type',
    "ENUM('ORDINARIA','EXTRAORDINARIA') NOT NULL DEFAULT 'ORDINARIA' COMMENT 'Natureza assembleia Ordinaria ou Extraordinaria'");

-- entity_name: razão social da entidade/condomínio digitada pelo admin (prioridade alta no PDF)
CALL mig_add_col_if_not_exists('elections', 'entity_name',
    'VARCHAR(255) DEFAULT NULL COMMENT ''Razão social da entidade/condomínio (cabeçalho PDF)''');

-- church_legal_name: razão social da igreja (snapshot no momento da criação, fallback)
CALL mig_add_col_if_not_exists('elections', 'church_legal_name',
    'VARCHAR(255) DEFAULT NULL COMMENT ''Razão social da igreja (fallback cabeçalho PDF)''');

-- church_name: nome fantasia da igreja (snapshot no momento da criação, último fallback)
CALL mig_add_col_if_not_exists('elections', 'church_name',
    'VARCHAR(160) DEFAULT NULL COMMENT ''Nome fantasia da igreja (fallback PDF)''');

-- Backfill: copia os valores da igreja para assembleias que já existiam
--           (apenas se as colunas recém forem adicionadas e estiverem NULL)
UPDATE elections e
 INNER JOIN churches c ON c.id = e.church_id
   SET e.church_name       = COALESCE(e.church_name,       c.name),
       e.church_legal_name = COALESCE(e.church_legal_name, c.legal_name)
 WHERE (e.church_name IS NULL OR e.church_legal_name IS NULL);

-- Limpeza
DROP PROCEDURE IF EXISTS `mig_add_col_if_not_exists`;
