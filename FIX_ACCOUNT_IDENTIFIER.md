# Fix: Account Identifier Column

## Problema Identificado

O sistema estava usando incorretamente `merchant_id as account_identifier` nas queries SQL. Isso causava confusão porque:

- `merchant_id` é o **x-withdraw-key** usado apenas para operações de cashout
- `account_identifier` deveria ser um identificador único para exibição e rastreamento

## O Que Foi Corrigido

### 1. Scripts de Migration
- ✅ `apply_acquirer_accounts_migration.php` - Atualizado para incluir coluna `account_identifier`
- ✅ `add_account_identifier.php` - Novo script para adicionar coluna em bancos existentes

### 2. Models
- ✅ `app/models/SellerAcquirerAccount.php`
  - Método `getBySellerWithDetails()` - Agora usa `aa.account_identifier`
  - Método `getActiveAccountsForSeller()` - Agora retorna `aa.merchant_id` separadamente

### 3. Controllers
- ✅ `app/controllers/web/AdminController.php`
  - Método `getAvailableAccounts()` - Corrigido query SQL

### 4. Documentação
- ✅ `ACCOUNT_IDENTIFIERS.md` - Nova documentação detalhada sobre os três identificadores
- ✅ `MULTI_ACCOUNT_ACQUIRER_SYSTEM.md` - Atualizado com clarificação dos identificadores

## Como Aplicar a Correção

### Para Novo Banco de Dados

Se você ainda não executou a migration, basta executar:

```bash
php apply_acquirer_accounts_migration.php
```

Isso criará a tabela já com a coluna `account_identifier` correta.

### Para Banco de Dados Existente

Se você já tem a tabela `acquirer_accounts` mas sem a coluna `account_identifier`, execute:

```bash
php add_account_identifier.php
```

Este script irá:
1. Adicionar a coluna `account_identifier`
2. Popular contas existentes com identificadores no formato `ACC-000001`, `ACC-000002`, etc
3. Adicionar constraint de unicidade

### Verificar Correção

Após executar a correção, você pode verificar com:

```sql
SELECT
    id,
    name,
    account_identifier,
    client_id,
    merchant_id
FROM acquirer_accounts;
```

Você deve ver algo como:

```
+----+-------------------+--------------------+-----------------+------------------+
| id | name              | account_identifier | client_id       | merchant_id      |
+----+-------------------+--------------------+-----------------+------------------+
|  1 | Conta Principal 1 | ACC-000001         | client_id_1     | withdraw_key_001 |
|  2 | Conta Principal 2 | ACC-000002         | client_id_2     | withdraw_key_002 |
+----+-------------------+--------------------+-----------------+------------------+
```

## Estrutura Correta

```sql
CREATE TABLE acquirer_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  acquirer_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  account_identifier VARCHAR(255) NULL,          -- ✅ Identificador de exibição
  client_id VARCHAR(255),                        -- ✅ Autenticação
  client_secret VARCHAR(255),                    -- ✅ Autenticação
  merchant_id VARCHAR(255),                      -- ✅ x-withdraw-key (cashout only)
  -- ... outros campos
  UNIQUE KEY unique_account_identifier (acquirer_id, account_identifier)
);
```

## Uso Correto dos Identificadores

### Para Autenticação (Cashin e Cashout)
```php
$authToken = base64_encode($account['client_id'] . ':' . $account['client_secret']);
$headers = ['Authorization' => 'Basic ' . $authToken];
```

### Para Cashout (adicionar x-withdraw-key)
```php
if ($operationType === 'cashout') {
    $headers['x-withdraw-key'] = $account['merchant_id'];
}
```

### Para Exibir na Interface
```php
echo "Conta: " . $account['account_identifier'];
// Exibe: "Conta: ACC-000001"
```

## Próximos Passos

1. Execute o script de correção apropriado
2. Verifique se todas as contas têm `account_identifier` populado
3. Teste as páginas de administração (especialmente "Gerenciar Contas do Seller")
4. Teste criação de novas transações para garantir que está usando as credenciais corretas

## Documentação Adicional

- [ACCOUNT_IDENTIFIERS.md](ACCOUNT_IDENTIFIERS.md) - Explicação detalhada dos três identificadores
- [MULTI_ACCOUNT_ACQUIRER_SYSTEM.md](MULTI_ACCOUNT_ACQUIRER_SYSTEM.md) - Sistema de múltiplas contas
- [API_ROUTES_ACQUIRER_ACCOUNTS.md](API_ROUTES_ACQUIRER_ACCOUNTS.md) - Rotas da API

## Suporte

Se encontrar problemas após aplicar a correção:

1. Verifique os logs em `app/logs/`
2. Confirme que a coluna `account_identifier` existe:
   ```sql
   DESCRIBE acquirer_accounts;
   ```
3. Confirme que todas as contas têm valores populados:
   ```sql
   SELECT id, name, account_identifier FROM acquirer_accounts WHERE account_identifier IS NULL;
   ```
   (Deve retornar 0 linhas)
