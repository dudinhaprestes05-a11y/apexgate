# Integração PodPay - Implementação Completa

## ✅ Já Implementado

### 1. Service de Integração
**Arquivo:** `/app/services/PodPayService.php`

Métodos implementados:
- `createPixTransaction()` - Cria PIX na PodPay
- `createTransfer()` - Cria cashout na PodPay
- `consultTransaction()` - Consulta transação PIX
- `consultTransfer()` - Consulta transferência
- `parseWebhook()` - Parse de webhooks recebidos
- Mapeamento completo de status

### 2. Banco de Dados Atualizado
**Arquivo:** `/sql/schema.sql`

Campos adicionados:
- `external_id` em `pix_cashin` e `pix_cashout`
- `customer_name`, `customer_document`, `customer_email` em `pix_cashin`
- `pix_key_type` com suporte a 'copypaste' e 'evp'
- `webhook_attempts` para controle de retries
- Status mapeados: `waiting_payment`, `approved`, `refused`, `PENDING_QUEUE`, `COMPLETED`

### 3. Worker de Retry
**Arquivo:** `/app/workers/retry_failed_callbacks.php`

Implementa:
- Retry exponencial de webhooks falhados
- Respeita max_attempts configurado
- Logs detalhados de cada tentativa

## 📋 Próximos Passos (Para Completar)

### 1. Atualizar Controllers API

#### PixController.php
```php
// ADICIONAR suporte a external_id
// TROCAR AcquirerService por PodPayService
// ADICIONAR dados de customer no payload
```

#### CashoutController.php
```php
// ADICIONAR suporte a external_id
// ADICIONAR suporte a pix_key_type: copypaste
// INTEGRAR com PodPayService::createTransfer()
```

#### WebhookController.php
```php
// INTEGRAR PodPayService::parseWebhook()
// MAPEAR status corretamente (approved -> paid, etc)
// PROCESSAR webhooks tipo 'transaction' e 'withdraw'
```

### 2. Criar Painéis Admin e Seller

#### Admin Panel
- Login/Autenticação
- Dashboard com estatísticas
- Gerenciamento de sellers
- Gerenciamento de adquirentes
- Visualização de logs
- Relatórios financeiros

#### Seller Panel
- Login/Autenticação
- Dashboard com métricas
- Lista de transações
- Extrato/Saldo
- Configurações (webhook URL, etc)
- Documentação da API

### 3. Atualizar Documentação

Adicionar na API_DOCUMENTATION.md:
- Formato exato do payload PodPay
- Exemplo de external_id
- Formato customer object
- Status completos (waiting_payment, approved, refused)
- Formato dos webhooks (type: transaction/withdraw)

## 🔧 Configuração da Adquirente PodPay

### Inserir no Banco:

```sql
INSERT INTO acquirers (name, code, api_url, api_key, api_secret, priority_order, status, daily_limit, daily_reset_at, config)
VALUES (
    'PodPay',
    'podpay',
    'https://api.podpay.co',
    'YOUR_API_KEY_HERE',
    'YOUR_API_SECRET_HERE',
    1,
    'active',
    1000000.00,
    CURDATE(),
    '{"withdraw_key": "YOUR_WITHDRAW_KEY_HERE"}'
);
```

### Configurações Necessárias:
- `api_key` - Chave da API PodPay
- `api_secret` - Secret da API PodPay
- `config.withdraw_key` - Chave para operações de saque

## 📡 Endpoints PodPay

### Cash-in (Criar PIX)
```
POST https://api.podpay.co/v1/transactions
Authorization: Basic base64(api_key:api_secret)
Content-Type: application/json

{
  "amount": 10000,
  "currency": "BRL",
  "paymentMethod": "pix",
  "items": [...],
  "customer": {...},
  "postbackUrl": "https://gateway.com/api/webhook/acquirer?acquirer=podpay"
}
```

### Cash-out (Transferência)
```
POST https://api.podpay.co/v1/transfers
Authorization: Basic base64(api_key:api_secret)
Content-Type: application/json
x-withdraw-key: WITHDRAW_KEY

{
  "method": "fiat",
  "amount": 6260,
  "pixKey": "00020101...",
  "pixKeyType": "copypaste",
  "netPayout": true
}
```

## 🔄 Mapeamento de Status

### Cash-in (Transações PIX)
| PodPay Status | Nossa Gateway | Descrição |
|---------------|---------------|-----------|
| waiting_payment | waiting_payment | Aguardando pagamento |
| pending | pending | Processando |
| approved | paid | Pago |
| paid | paid | Confirmado |
| refused | failed | Recusado |
| cancelled | cancelled | Cancelado |
| expired | expired | Expirado |

### Cash-out (Transferências)
| PodPay Status | Nossa Gateway | Descrição |
|---------------|---------------|-----------|
| PENDING_QUEUE | processing | Na fila |
| pending | processing | Processando |
| processing | processing | Em processamento |
| COMPLETED | completed | Concluído |
| completed | completed | Concluído |
| failed | failed | Falhou |
| cancelled | cancelled | Cancelado |

## 📥 Webhooks Recebidos (PodPay → Gateway)

### Formato Transaction
```json
{
  "type": "transaction",
  "data": {
    "id": 12345,
    "status": "approved",
    "amount": 10000,
    "pix": {
      "qrcode": "00020101...",
      "end2EndId": "E12345678...",
      "expirationDate": "2025-11-28T23:59:59Z"
    }
  }
}
```

### Formato Withdraw
```json
{
  "type": "withdraw",
  "data": {
    "id": 999,
    "amount": 6390,
    "netAmount": 6260,
    "fee": 130,
    "status": "COMPLETED",
    "pixKey": "00020101...",
    "pixKeyType": "copypaste"
  }
}
```

## 📤 Webhooks Enviados (Gateway → Seller)

### Formato Cash-in
```json
{
  "type": "pix.cashin",
  "pix_id": 1234,
  "external_id": "ABC123",
  "status": "paid",
  "amount": 100.50,
  "paid_at": "2025-11-28T12:00:00Z"
}
```

### Formato Cash-out
```json
{
  "type": "pix.cashout",
  "cashout_id": 999,
  "external_id": "SAC123",
  "status": "completed",
  "net_amount": 480.00,
  "fee": 20.00
}
```

## ✅ Checklist de Implementação

- [x] PodPayService criado com todos os métodos
- [x] Schema atualizado com external_id e customer
- [x] Worker retry_failed_callbacks.php criado
- [ ] PixController atualizado com PodPay
- [ ] CashoutController atualizado com PodPay
- [ ] WebhookController processando PodPay
- [ ] Admin Panel criado
- [ ] Seller Panel criado
- [ ] Documentação API atualizada
- [ ] Testes de integração realizados

## 🧪 Teste de Integração

### 1. Criar Transação PIX
```bash
curl -X POST http://localhost/api/pix/create \
  -H "X-API-Key: sk_test_demo_key_123456789" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "TEST001",
    "amount": 10.00,
    "customer": {
      "name": "Cliente Teste",
      "document": "12345678900",
      "email": "teste@example.com"
    }
  }'
```

### 2. Simular Webhook da PodPay
```bash
curl -X POST "http://localhost/api/webhook/acquirer?acquirer=podpay" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "transaction",
    "data": {
      "id": 12345,
      "status": "approved",
      "amount": 1000,
      "pix": {
        "end2EndId": "E12345678..."
      }
    }
  }'
```

### 3. Verificar Webhook Enviado ao Seller
Verificar tabela `webhooks_queue` e logs do worker `process_webhooks.php`

## 🔒 Segurança PodPay

- **Autenticação:** Basic Auth (base64)
- **Webhook:** Validar IP de origem
- **Timeout:** 30 segundos
- **Retry:** Automático pela PodPay
- **Logs:** Registrar todas as interações

## 💡 Dicas Importantes

1. **Valores:** PodPay usa centavos (R$ 100,00 = 10000)
2. **Documentos:** Remover formatação (só números)
3. **PIX Key:** Copypaste completo deve ter 1000+ caracteres
4. **Status:** Sempre mapear para nossos status internos
5. **Webhooks:** Processar de forma assíncrona
6. **Errors:** Logar detalhadamente para debug

## 📞 Suporte PodPay

- Documentação: https://docs.podpay.co
- Suporte: suporte@podpay.co
- Status da API: https://status.podpay.co
