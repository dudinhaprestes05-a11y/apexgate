-- Configuração do Acquirer PodPay
-- Execute este script para configurar corretamente o PodPay

-- Primeiro, verifica se o acquirer já existe
INSERT INTO acquirers (
    name,
    code,
    api_url,
    api_key,
    api_secret,
    status,
    priority_order,
    daily_limit,
    daily_used,
    min_transaction_amount,
    max_transaction_amount,
    fee_percentage,
    supports_cashin,
    supports_cashout,
    supports_split,
    created_at,
    updated_at
)
VALUES (
    'PodPay',
    'podpay',
    'COLE_AQUI_A_URL_DA_API_PODPAY',  -- Ex: https://api.podpay.com.br
    'COLE_AQUI_SUA_API_KEY',          -- API Key do PodPay
    'COLE_AQUI_SEU_API_SECRET',       -- API Secret do PodPay
    'active',
    1,
    1000000.00,
    0.00,
    1.00,
    10000.00,
    0.00,
    1,
    1,
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    api_url = VALUES(api_url),
    api_key = VALUES(api_key),
    api_secret = VALUES(api_secret),
    status = VALUES(status),
    updated_at = NOW();

-- Verificar se a configuração está correta
SELECT
    id,
    name,
    code,
    api_url,
    CASE
        WHEN api_key IS NULL OR api_key = '' THEN '❌ NÃO CONFIGURADO'
        ELSE '✅ CONFIGURADO'
    END as api_key_status,
    CASE
        WHEN api_secret IS NULL OR api_secret = '' THEN '❌ NÃO CONFIGURADO'
        ELSE '✅ CONFIGURADO'
    END as api_secret_status,
    status,
    priority_order
FROM acquirers
WHERE code = 'podpay';
