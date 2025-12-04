# Account Identifiers Documentation

## Overview

The acquirer accounts system uses three distinct identifiers for different purposes. Understanding the difference between these identifiers is critical for correct implementation.

## The Three Identifiers

### 1. `client_id` + `client_secret`
- **Purpose**: Authentication credentials for API requests
- **Usage**: Used in all API calls to the acquirer (both cashin and cashout)
- **Format**: Base64 encoded as `Basic Auth` header
- **Example**: `Authorization: Basic base64(client_id:client_secret)`
- **PodPay Usage**: Required for all transactions and transfer requests

### 2. `merchant_id`
- **Purpose**: Withdrawal authorization key (x-withdraw-key)
- **Usage**: Used ONLY for cashout/transfer operations
- **Format**: Sent as custom header `x-withdraw-key`
- **Example**: `x-withdraw-key: withdraw_key_001`
- **PodPay Usage**: Required in `/v1/transfers` endpoint and transfer consultation
- **Important**: This is NOT an account identifier, it's a security key for withdrawals

### 3. `account_identifier`
- **Purpose**: Human-readable account identification for display and tracking
- **Usage**: Used in UI, logs, and internal references
- **Format**: String (typically `ACC-XXXXXX` format)
- **Example**: `ACC-000001`, `ACC-000002`
- **Important**: This is for display/tracking only, not for API authentication

## Database Schema

```sql
CREATE TABLE acquirer_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  acquirer_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  account_identifier VARCHAR(255) NULL,           -- Display identifier
  client_id VARCHAR(255),                         -- Auth username
  client_secret VARCHAR(255),                     -- Auth password
  merchant_id VARCHAR(255),                       -- x-withdraw-key for cashout
  balance DECIMAL(15,2) DEFAULT 0.00,
  -- ... other fields
  UNIQUE KEY unique_account_identifier (acquirer_id, account_identifier)
);
```

## Common Mistakes to Avoid

### ❌ Wrong: Using merchant_id as account identifier
```php
// DON'T DO THIS
$sql = "SELECT aa.merchant_id as account_identifier FROM acquirer_accounts aa";
```

### ✅ Correct: Using proper account_identifier
```php
// DO THIS
$sql = "SELECT aa.account_identifier FROM acquirer_accounts aa";
```

### ❌ Wrong: Using account_identifier for authentication
```php
// DON'T DO THIS
$authToken = base64_encode($account['account_identifier'] . ':' . $secret);
```

### ✅ Correct: Using client_id and client_secret
```php
// DO THIS
$authToken = base64_encode($account['client_id'] . ':' . $account['client_secret']);
```

### ❌ Wrong: Sending merchant_id in all requests
```php
// DON'T DO THIS
$headers = ['x-withdraw-key' => $merchantId]; // For cashin requests
```

### ✅ Correct: Only send merchant_id for cashout operations
```php
// DO THIS
if ($operationType === 'cashout') {
    $headers = ['x-withdraw-key' => $merchantId];
}
```

## Implementation in PodPayService

The `PodPayService` correctly implements these identifiers:

```php
class PodPayService {
    private $authToken;      // Built from client_id:client_secret
    private $withdrawKey;    // From merchant_id or config.withdraw_key

    public function __construct($acquirer) {
        // Setup auth for ALL requests
        $clientId = $acquirer['client_id'] ?? $acquirer['api_key'] ?? '';
        $clientSecret = $acquirer['client_secret'] ?? $acquirer['api_secret'] ?? '';
        $this->authToken = base64_encode($clientId . ':' . $clientSecret);

        // Setup withdraw key for CASHOUT only
        $config = json_decode($acquirer['config'] ?? '{}', true) ?? [];
        $this->withdrawKey = $config['withdraw_key'] ?? $acquirer['merchant_id'] ?? null;
    }

    public function createPixTransaction($data) {
        // Uses authToken only (no withdraw key needed)
        $response = $this->sendRequest('/v1/transactions', $payload, 'POST');
    }

    public function createTransfer($data) {
        // Uses authToken + withdraw key
        $response = $this->sendRequest('/v1/transfers', $payload, 'POST', [
            'x-withdraw-key' => $this->withdrawKey
        ]);
    }
}
```

## Query Examples

### Listing Accounts for Admin
```php
SELECT
    aa.id,
    aa.name,
    aa.account_identifier,    -- For display
    aa.client_id,             -- For auth info
    aa.merchant_id,           -- For cashout capability info
    a.name as acquirer_name
FROM acquirer_accounts aa
JOIN acquirers a ON a.id = aa.acquirer_id
WHERE aa.is_active = 1
```

### Getting Seller's Active Accounts
```php
SELECT
    saa.*,
    aa.account_identifier,    -- For display
    aa.client_id,             -- For authentication
    aa.client_secret,         -- For authentication
    aa.merchant_id,           -- For cashout operations
    a.code as acquirer_type
FROM seller_acquirer_accounts saa
JOIN acquirer_accounts aa ON saa.acquirer_account_id = aa.id
JOIN acquirers a ON aa.acquirer_id = a.id
WHERE saa.seller_id = ? AND saa.is_active = 1
```

## Migration Notes

If you have existing accounts without `account_identifier`, run:

```php
php add_account_identifier.php
```

Or execute the migration script:
```php
php apply_acquirer_accounts_migration.php
```

This will:
1. Add the `account_identifier` column if missing
2. Populate existing accounts with `ACC-XXXXXX` format
3. Add unique constraint for account_identifier per acquirer

## Summary

| Identifier | Purpose | Used In | Required For |
|------------|---------|---------|--------------|
| `client_id` + `client_secret` | API Authentication | All requests | Cashin & Cashout |
| `merchant_id` | Withdrawal authorization | Cashout requests only | Cashout operations |
| `account_identifier` | Display/Tracking | UI, logs, reports | Internal reference |

Remember: **Never confuse these three identifiers**. Each has a specific purpose and using them incorrectly will cause authentication failures or incorrect account tracking.
