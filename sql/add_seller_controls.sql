/*
  # Adicionar Controles Administrativos para Sellers

  Adiciona campos para o admin ter controle total sobre as operações dos sellers:
  - Ativar/desativar cash-in e cash-out individualmente
  - Bloqueio temporário e permanente
  - Retenção de saldo
  - Porcentagem de retenção do faturamento
*/

ALTER TABLE `sellers`
ADD COLUMN `cashin_enabled` BOOLEAN DEFAULT TRUE COMMENT 'Permitir recebimentos PIX' AFTER `fee_fixed_cashout`,
ADD COLUMN `cashout_enabled` BOOLEAN DEFAULT TRUE COMMENT 'Permitir saques PIX' AFTER `cashin_enabled`,
ADD COLUMN `temporarily_blocked` BOOLEAN DEFAULT FALSE COMMENT 'Bloqueio temporário' AFTER `cashout_enabled`,
ADD COLUMN `permanently_blocked` BOOLEAN DEFAULT FALSE COMMENT 'Bloqueio permanente' AFTER `temporarily_blocked`,
ADD COLUMN `blocked_reason` TEXT DEFAULT NULL COMMENT 'Motivo do bloqueio' AFTER `permanently_blocked`,
ADD COLUMN `blocked_at` TIMESTAMP NULL COMMENT 'Data do bloqueio' AFTER `blocked_reason`,
ADD COLUMN `blocked_by` INT UNSIGNED DEFAULT NULL COMMENT 'Admin que bloqueou' AFTER `blocked_at`,
ADD COLUMN `balance_retention` BOOLEAN DEFAULT FALSE COMMENT 'Reter saldo' AFTER `blocked_by`,
ADD COLUMN `revenue_retention_percentage` DECIMAL(5,2) DEFAULT 0.00 COMMENT '% de retenção do faturamento' AFTER `balance_retention`,
ADD COLUMN `retention_reason` TEXT DEFAULT NULL COMMENT 'Motivo da retenção' AFTER `revenue_retention_percentage`,
ADD COLUMN `retention_started_at` TIMESTAMP NULL COMMENT 'Início da retenção' AFTER `retention_reason`,
ADD COLUMN `retention_started_by` INT UNSIGNED DEFAULT NULL COMMENT 'Admin que iniciou retenção' AFTER `retention_started_at`,
ADD INDEX `idx_cashin_enabled` (`cashin_enabled`),
ADD INDEX `idx_cashout_enabled` (`cashout_enabled`),
ADD INDEX `idx_blocked` (`temporarily_blocked`, `permanently_blocked`);
