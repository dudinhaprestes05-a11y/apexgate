/*
  # Adicionar webhook_secret para sellers

  ## Alterações
  - Adiciona coluna webhook_secret na tabela sellers
  - Este secret é usado para assinar webhooks com HMAC
  - É independente do api_secret e pode ser mostrado ao seller
  - Gerado automaticamente para sellers existentes

  ## Notas
  - webhook_secret é armazenado em texto plano (hex)
  - api_secret continua sendo armazenado como hash SHA256
  - Sellers podem regenerar o webhook_secret independentemente
*/

-- Adicionar coluna webhook_secret
ALTER TABLE sellers
ADD COLUMN IF NOT EXISTS webhook_secret VARCHAR(255) DEFAULT NULL
AFTER webhook_url;

-- Gerar webhook_secret para sellers existentes que não têm
UPDATE sellers
SET webhook_secret = MD5(CONCAT(id, api_key, UNIX_TIMESTAMP()))
WHERE webhook_secret IS NULL;

-- Adicionar índice para otimizar buscas
CREATE INDEX IF NOT EXISTS idx_sellers_webhook_secret ON sellers(webhook_secret);
