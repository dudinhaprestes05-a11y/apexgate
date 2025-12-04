/*
  # Remover Limite Diário Genérico (daily_limit)

  ## Resumo
  Remove os campos `daily_limit`, `daily_used` e `daily_reset_at` das tabelas `sellers` e `acquirers`.
  Estes campos não são mais necessários, pois o limite diário agora é específico apenas para cashout
  através do campo `cashout_daily_limit`.

  ## Tabelas Afetadas

  ### 1. Tabela `sellers`
    - Remove `daily_limit` - Limite diário genérico (obsoleto)
    - Remove `daily_used` - Total usado no dia (obsoleto)
    - Remove `daily_reset_at` - Data de reset do limite (obsoleto)

    **Nota:** O campo `cashout_daily_limit` é mantido pois é específico para operações de saque.

  ### 2. Tabela `acquirers`
    - Remove `daily_limit` - Limite diário da adquirente (obsoleto)
    - Remove `daily_used` - Total usado no dia (obsoleto)
    - Remove `daily_reset_at` - Data de reset (obsoleto)

  ## Motivo da Remoção
  - Limite diário para cash-in não é necessário
  - Limite diário é controlado apenas no cash-out através de `cashout_daily_limit`
  - Simplifica a estrutura do banco de dados
  - Remove campos não utilizados no código

  ## Campos Mantidos (Cash-out específicos)
  - `cashout_daily_limit` - Limite diário específico para cashout
  - `cashout_daily_used` - Total usado no dia para cashout
  - `cashout_daily_reset_at` - Data de reset do limite de cashout

  ## Segurança
  - Esta operação é segura pois os campos não são mais utilizados no sistema
  - Backup recomendado antes de executar
*/

-- ============================================================================
-- STEP 1: Remover colunas da tabela sellers
-- ============================================================================

ALTER TABLE sellers
  DROP COLUMN IF EXISTS daily_limit,
  DROP COLUMN IF EXISTS daily_used,
  DROP COLUMN IF EXISTS daily_reset_at;

-- ============================================================================
-- STEP 2: Remover colunas da tabela acquirers (se ainda existirem)
-- ============================================================================

-- Verificar se as colunas existem antes de remover
ALTER TABLE acquirers
  DROP COLUMN IF EXISTS daily_limit,
  DROP COLUMN IF EXISTS daily_used,
  DROP COLUMN IF EXISTS daily_reset_at;

-- ============================================================================
-- STEP 3: Verificação
-- ============================================================================

-- Verificar estrutura da tabela sellers após remoção
SELECT
    'sellers' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'sellers'
    AND TABLE_SCHEMA = DATABASE()
    AND COLUMN_NAME LIKE '%daily%'
ORDER BY ORDINAL_POSITION;

-- Verificar estrutura da tabela acquirers após remoção
SELECT
    'acquirers' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'acquirers'
    AND TABLE_SCHEMA = DATABASE()
    AND COLUMN_NAME LIKE '%daily%'
ORDER BY ORDINAL_POSITION;

-- ============================================================================
-- Resultado Esperado:
-- - Sellers deve ter apenas: cashout_daily_limit, cashout_daily_used, cashout_daily_reset_at
-- - Acquirers não deve ter nenhum campo com 'daily' no nome
-- ============================================================================
