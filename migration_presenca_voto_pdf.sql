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
