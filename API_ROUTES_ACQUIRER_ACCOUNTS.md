# Rotas da API - Sistema de Contas de Adquirente

## Gerenciamento de Contas de Adquirente

### Listar contas de uma adquirente
```
GET /admin/acquirers/{acquirer_id}/accounts
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "accounts": [
    {
      "id": 1,
      "acquirer_id": 1,
      "name": "Conta Principal",
      "client_id": "xxx",
      "client_secret": "xxx",
      "merchant_id": "xxx",
      "balance": 10000.00,
      "is_active": true,
      "created_at": "2024-01-01 00:00:00"
    }
  ]
}
```

---

### Criar conta de adquirente
```
POST /admin/acquirers/{acquirer_id}/accounts/create
```

**Parâmetros (form-data):**
- `name` (obrigatório): Nome da conta
- `client_id` (obrigatório): Client ID
- `client_secret` (obrigatório): Client Secret
- `merchant_id` (obrigatório): Merchant ID
- `balance` (opcional): Saldo inicial
- `is_active` (opcional): Status (true/false)

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conta criada com sucesso!",
  "id": 1
}
```

---

### Atualizar conta de adquirente
```
POST /admin/accounts/{account_id}/update
```

**Parâmetros (form-data):**
- `name` (obrigatório): Nome da conta
- `client_id` (obrigatório): Client ID
- `client_secret` (obrigatório): Client Secret
- `merchant_id` (obrigatório): Merchant ID
- `balance` (opcional): Saldo
- `is_active` (opcional): Status (true/false)

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conta atualizada com sucesso!"
}
```

---

### Excluir conta de adquirente
```
POST /admin/accounts/{account_id}/delete
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conta excluída com sucesso!"
}
```

---

## Gerenciamento de Contas por Seller

### Listar contas atribuídas a um seller
```
GET /admin/sellers/{seller_id}/accounts
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "accounts": [
    {
      "id": 1,
      "seller_id": 1,
      "acquirer_account_id": 1,
      "account_name": "Conta Principal",
      "acquirer_name": "PodPay",
      "acquirer_code": "podpay",
      "priority": 1,
      "distribution_strategy": "priority_only",
      "percentage_allocation": 0,
      "is_active": true,
      "total_transactions": 150,
      "total_volume": 50000.00,
      "last_used_at": "2024-01-01 12:00:00",
      "balance": 10000.00,
      "account_active": true
    }
  ]
}
```

---

### Atribuir conta a um seller
```
POST /admin/sellers/{seller_id}/accounts/assign
```

**Parâmetros (form-data):**
- `account_id` (obrigatório): ID da conta de adquirente
- `priority` (opcional, padrão: 1): Ordem de prioridade (1 = maior prioridade)
- `strategy` (opcional, padrão: "priority_only"): Estratégia de distribuição
  - `priority_only`: Usa sempre a conta de maior prioridade
  - `round_robin`: Alterna entre contas
  - `least_used`: Usa a conta menos utilizada
  - `percentage`: Distribui por percentual
- `percentage` (opcional, padrão: 0): Percentual de alocação (0-100)

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conta atribuída com sucesso!"
}
```

---

### Remover conta de um seller
```
POST /admin/sellers/{seller_id}/accounts/{account_id}/remove
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Conta removida com sucesso!"
}
```

---

### Ativar/Desativar conta de um seller
```
POST /admin/sellers/{seller_id}/accounts/{account_id}/toggle
```

**Parâmetros (form-data):**
- `is_active` (obrigatório): Status (true/false)

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Status atualizado com sucesso!"
}
```

---

## Estratégias de Distribuição

### priority_only (Padrão)
Usa sempre a conta com maior prioridade (menor número). Se falhar, tenta a próxima.

**Exemplo:**
- Conta A (prioridade 1) - tenta primeiro
- Conta B (prioridade 2) - fallback automático se A falhar

---

### round_robin
Alterna entre as contas disponíveis de forma circular.

**Exemplo:**
- Transação 1: Conta A
- Transação 2: Conta B
- Transação 3: Conta A
- etc...

---

### least_used
Usa a conta com menor número de transações processadas.

**Exemplo:**
- Conta A: 100 transações
- Conta B: 50 transações → escolhida
- Conta C: 75 transações

---

### percentage
Distribui transações baseado no percentual configurado.

**Exemplo:**
- Conta A: 70% das transações
- Conta B: 30% das transações

---

## Fallback Automático

O sistema automaticamente tenta outras contas quando ocorre:

### Erros Recuperáveis (faz fallback)
- Saldo insuficiente
- Limite excedido
- Timeout
- Erro de conexão
- Serviço indisponível

### Erros Não-recuperáveis (não faz fallback)
- Dados inválidos
- Chave PIX inválida
- Documento inválido
- Erro de autenticação

---

## Exemplo de Uso Completo

### 1. Criar duas contas PodPay
```bash
# Conta Principal
curl -X POST http://localhost/admin/acquirers/1/accounts/create \
  -d "name=Conta Principal" \
  -d "client_id=xxx" \
  -d "client_secret=xxx" \
  -d "merchant_id=xxx"

# Conta Backup
curl -X POST http://localhost/admin/acquirers/1/accounts/create \
  -d "name=Conta Backup" \
  -d "client_id=yyy" \
  -d "client_secret=yyy" \
  -d "merchant_id=yyy"
```

### 2. Atribuir ambas ao seller com prioridades
```bash
# Conta Principal (prioridade 1)
curl -X POST http://localhost/admin/sellers/1/accounts/assign \
  -d "account_id=1" \
  -d "priority=1" \
  -d "strategy=priority_only"

# Conta Backup (prioridade 2)
curl -X POST http://localhost/admin/sellers/1/accounts/assign \
  -d "account_id=2" \
  -d "priority=2" \
  -d "strategy=priority_only"
```

### 3. Criar cashout (usa automaticamente com fallback)
```bash
curl -X POST http://localhost/api/cashout/create \
  -H "X-API-Key: seller_api_key" \
  -d '{
    "amount": 100.00,
    "pix_key": "11999999999",
    "pix_key_type": "phone",
    "beneficiary_name": "João Silva",
    "beneficiary_document": "12345678901"
  }'
```

**Fluxo:**
1. Tenta na Conta Principal (prioridade 1)
2. Se retornar "saldo insuficiente", automaticamente tenta na Conta Backup (prioridade 2)
3. Se sucesso, retorna dados da transação
4. Se ambas falharem, retorna erro

---

## Logs e Monitoramento

Todas as tentativas são registradas nos logs do sistema:

```sql
SELECT * FROM logs
WHERE category = 'acquirer'
AND message LIKE '%account%'
ORDER BY created_at DESC;
```

Informações logadas:
- Conta selecionada
- Tentativas de fallback
- Sucessos e falhas
- Tempo de resposta
- Motivo de erro
