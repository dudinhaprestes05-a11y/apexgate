# Correção de Autenticação - Headers Case-Insensitive

## Problema Identificado

A autenticação Basic Auth estava falhando com erro 401 "Authentication is required" mesmo quando o header `Authorization` era enviado corretamente.

**Causa:** A função `getallheaders()` retorna headers com capitalização diferente dependendo do servidor web:
- **Apache**: Headers com primeira letra maiúscula (ex: "Authorization")
- **Nginx/PHP-FPM**: Headers podem vir em lowercase (ex: "authorization")
- **FastCGI**: Headers vêm através de `$_SERVER` com prefixo `HTTP_`

## Solução Implementada

### 1. Nova Função Helper (`helpers.php`)

Foi criada a função `getAllHeadersCaseInsensitive()` que:
- Normaliza todos os headers para formato consistente (Ucwords)
- Funciona em qualquer servidor (Apache, Nginx, PHP-FPM)
- Faz fallback automático para `$_SERVER` quando necessário
- Garante que "Authorization", "X-Api-Key", "X-Signature" sejam encontrados independente da capitalização

### 2. Atualização do AuthService

O `AuthService.php` foi atualizado para:
- Usar `getAllHeadersCaseInsensitive()` ao invés de `getallheaders()`
- Buscar headers normalizados: "Authorization", "X-Api-Key", "X-Signature"
- Adicionar logs de debug em ambiente de desenvolvimento
- Usar regex case-insensitive (`/i`) para "Basic" e "Bearer"

### 3. Atualização do WebhookController

O `WebhookController.php` também foi atualizado para usar a mesma função nos webhooks de adquirentes.

## Como Testar

### Teste 1: Basic Auth

```bash
curl -X POST http://seu-dominio.com/api/pix/create \
  -H "Authorization: Basic $(echo -n 'sua_api_key:seu_api_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "payer_name": "João Silva",
    "payer_document": "12345678901"
  }'
```

### Teste 2: Bearer Token

```bash
curl -X GET http://seu-dominio.com/api/pix/list \
  -H "Authorization: Bearer sua_api_key" \
  -H "Content-Type: application/json"
```

### Teste 3: X-API-Key Header

```bash
curl -X GET http://seu-dominio.com/api/pix/list \
  -H "X-API-Key: sua_api_key" \
  -H "Content-Type: application/json"
```

### Teste 4: X-API-Key + HMAC Signature

```bash
# Gerar signature HMAC
PAYLOAD='{"amount":100.00}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "seu_api_secret" | cut -d' ' -f2)

curl -X POST http://seu-dominio.com/api/pix/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-Signature: $SIGNATURE" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD"
```

## Debug em Desenvolvimento

Com `APP_ENV=development` no arquivo `.env`, o sistema agora loga informações úteis:

- Headers disponíveis quando autenticação falhar
- Presença do header Authorization
- Chaves dos headers recebidos (error_log)

## Resultados Esperados

✅ **Antes**: Erro 401 mesmo com headers corretos
✅ **Depois**: Autenticação funciona independente da capitalização dos headers
✅ **Compatibilidade**: Apache, Nginx, PHP-FPM, FastCGI

## Arquivos Modificados

1. `app/config/helpers.php` - Nova função `getAllHeadersCaseInsensitive()`
2. `app/services/AuthService.php` - Usa nova função + logs de debug
3. `app/controllers/api/WebhookController.php` - Usa nova função

## Notas Técnicas

A função normaliza headers da seguinte forma:
- `authorization` → `Authorization`
- `x-api-key` → `X-Api-Key`
- `x-signature` → `X-Signature`
- `content-type` → `Content-Type`

Isso garante consistência em todos os ambientes.
