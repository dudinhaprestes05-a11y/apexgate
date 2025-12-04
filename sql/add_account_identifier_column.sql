-- Add account_identifier column to acquirer_accounts table
-- This column is used for display and identification purposes
-- It's separate from merchant_id (x-withdraw-key) and client_id (authentication)

ALTER TABLE acquirer_accounts
ADD COLUMN account_identifier VARCHAR(255) NULL AFTER name;

-- Populate existing accounts with account_identifier based on name
UPDATE acquirer_accounts
SET account_identifier = CONCAT('ACC-', LPAD(id, 6, '0'))
WHERE account_identifier IS NULL;

-- Add unique constraint for account_identifier per acquirer
ALTER TABLE acquirer_accounts
ADD UNIQUE KEY unique_account_identifier (acquirer_id, account_identifier);
