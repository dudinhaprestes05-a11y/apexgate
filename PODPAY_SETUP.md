# Configuração do PodPay - Guia Completo

## Passo 1: Obter Credenciais do PodPay

Entre em contato com o PodPay e solicite:

1. **URL da API**: Ex: `https://api.podpay.com.br` ou `https://sandbox.podpay.com.br`
2. **API Key**: Sua chave de autenticação
3. **API Secret**: Seu segredo de autenticação

## Passo 2: Configurar no Banco de Dados

Execute o seguinte SQL substituindo os valores:

```sql
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
    'https://api.podpay.com.br',  -- Substitua pela URL correta
    'SUA_API_KEY_AQUI',           -- Substitua pela sua API Key
    'SEU_API_SECRET_AQUI',        -- Substitua pelo seu API Secret
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
```

## Passo 3: Verificar Configuração

Execute o script de verificação:

```bash
php check_acquirer.php
```

Ou acesse pelo navegador:
```
https://gate.apisafe.fun/check_acquirer.php
```

O retorno deve mostrar:
```json
{
  "status": "success",
  "acquirers": [
    {
      "name": "PodPay",
      "code": "podpay",
      "status": "active",
      "api_url": "https://api.podpay.com.br",
      "api_key_configured": "Sim",
      "configuration_ok": true,
      "issues": []
    }
  ]
}
```

## Passo 4: Testar Integração

### Teste 1: Criar uma Transação PIX

```bash
curl -X POST https://gate.apisafe.fun/api/pix/create \
  -u "sua_api_key:seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10.00,
    "customer": {
      "name": "Teste",
      "email": "teste@example.com",
      "document": "12345678900"
    }
  }'
```

### Resposta Esperada (Sucesso):
```json
{
  "success": true,
  "message": "PIX transaction created successfully",
  "data": {
    "transaction_id": "CASHIN_...",
    "amount": 10.00,
    "qrcode": "00020126330014...",
    "qrcode_base64": "iVBORw0KGgo...",
    "status": "pending",
    "expires_at": "2025-12-03 15:30:00"
  }
}
```

### Se Der Erro:

#### Erro 405 - Method Not Allowed
```json
{
  "success": false,
  "error": {
    "code": 500,
    "message": "Failed to create PIX transaction",
    "details": {
      "error": "HTTP 405: "
    }
  }
}
```

**Solução:**
- Verifique se a URL da API está correta
- Confirme com o PodPay qual é a URL atual
- Teste manualmente com cURL (veja abaixo)

#### Erro 401 - Unauthorized
```json
{
  "success": false,
  "error": {
    "code": 500,
    "message": "Failed to create PIX transaction",
    "details": {
      "error": "HTTP 401: Unauthorized"
    }
  }
}
```

**Solução:**
- Verifique se a API Key está correta
- Verifique se o API Secret está correto
- Confirme com o PodPay se as credenciais estão ativas

## Passo 5: Teste Manual com PodPay

Para confirmar que suas credenciais estão corretas, teste diretamente com o PodPay:

```bash
# Substitua:
# - API_URL: URL da API do PodPay
# - API_KEY: Sua API Key
# - API_SECRET: Seu API Secret

curl -X POST https://API_URL/v1/transactions \
  -u "API_KEY:API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "currency": "BRL",
    "paymentMethod": "pix",
    "items": [{
      "title": "Teste",
      "unitPrice": 1000,
      "quantity": 1,
      "tangible": false
    }],
    "customer": {
      "name": "Teste",
      "email": "teste@example.com",
      "document": {
        "number": "12345678900",
        "type": "cpf"
      }
    },
    "postbackUrl": "https://gate.apisafe.fun/api/webhook/acquirer?acquirer=podpay"
  }'
```

### Resposta Esperada do PodPay:
```json
{
  "id": "txn_...",
  "status": "waiting_payment",
  "amount": 1000,
  "pix": {
    "qrcode": "00020126330014...",
    "qrcodeBase64": "iVBORw0KGgo...",
    "key": "chave-pix@podpay.com.br",
    "expirationDate": "2025-12-03T15:30:00Z"
  }
}
```

## Passo 6: Verificar Logs

Se o teste falhar, verifique os logs detalhados:

```bash
# Via painel admin
https://gate.apisafe.fun/admin/logs

# Ou direto no banco de dados
SELECT * FROM logs
WHERE category = 'podpay'
ORDER BY created_at DESC
LIMIT 20;
```

Procure por mensagens como:
- `"Sending request"` - Mostra a URL e payload enviados
- `"HTTP error response"` - Mostra o código de erro e resposta
- `"PIX transaction created successfully"` - Confirma sucesso

## Estrutura de Payload Enviada

O gateway envia automaticamente para o PodPay:

```json
{
  "amount": 1000,              // Valor em centavos (R$ 10,00)
  "currency": "BRL",
  "paymentMethod": "pix",
  "items": [{
    "title": "Recebimento PIX",
    "unitPrice": 1000,
    "quantity": 1,
    "tangible": false
  }],
  "customer": {
    "name": "Nome do Cliente",
    "email": "cliente@example.com",
    "document": {
      "number": "12345678900",  // Somente números
      "type": "cpf"             // ou "cnpj" automaticamente
    }
  },
  "postbackUrl": "https://gate.apisafe.fun/api/webhook/acquirer?acquirer=podpay"
}
```

## Webhooks do PodPay

O gateway está configurado para receber webhooks do PodPay em:

```
https://gate.apisafe.fun/api/webhook/acquirer?acquirer=podpay
```

Configure esta URL no seu painel do PodPay para receber notificações de:
- Pagamento confirmado
- Pagamento expirado
- Pagamento cancelado

## Troubleshooting

### Problema: Erro 405 persiste após configuração correta

1. Verifique se o endpoint do PodPay mudou
2. Confirme com o suporte se é `/v1/transactions`
3. Teste com Postman ou similar
4. Verifique se não há proxy/firewall bloqueando

### Problema: Transação criada no gateway mas não no PodPay

✅ **CORRIGIDO!** O sistema agora:
1. Tenta criar no PodPay PRIMEIRO
2. Se falhar, retorna erro SEM criar no banco
3. Se sucesso, salva no banco com todos os dados

### Problema: Webhook não está sendo recebido

1. Verifique se configurou a URL no painel do PodPay
2. Teste manualmente:
```bash
curl -X POST https://gate.apisafe.fun/api/webhook/acquirer?acquirer=podpay \
  -H "Content-Type: application/json" \
  -d '{
    "type": "transaction",
    "data": {
      "id": "txn_test",
      "status": "paid",
      "amount": 1000
    }
  }'
```

## Checklist Final

- [ ] Credenciais do PodPay obtidas
- [ ] SQL executado com credenciais corretas
- [ ] `check_acquirer.php` retorna configuração OK
- [ ] Teste manual com cURL funciona
- [ ] Teste via API do gateway funciona
- [ ] Webhook configurado no PodPay
- [ ] Teste de webhook recebido com sucesso

## Suporte

Se após seguir todos os passos o problema persistir:

1. Execute `check_acquirer.php` e envie o resultado
2. Verifique `/admin/logs` e envie os últimos logs de `podpay`
3. Execute o teste manual com cURL e envie o resultado
4. Entre em contato com o suporte do PodPay com os logs
