/*
  # Add IP Whitelist System to Sellers

  This migration adds IP whitelist functionality to the sellers table.

  ## Changes

  1. New Columns in `sellers` table:
    - `ip_whitelist` (TEXT) - JSON array storing whitelisted IP addresses and CIDR ranges
    - `ip_whitelist_enabled` (BOOLEAN) - Flag to enable/disable IP whitelist verification

  2. Features:
    - Stores IP addresses and CIDR ranges in JSON format
    - When enabled, only requests from whitelisted IPs are allowed
    - When disabled, IP validation is bypassed
    - Default state is disabled for backward compatibility

  3. Security:
    - Adds additional layer of security for API access
    - Prevents unauthorized access even with valid credentials
    - Protects against credential theft and unauthorized usage
*/

-- Add IP whitelist columns to sellers table
ALTER TABLE sellers
ADD COLUMN IF NOT EXISTS ip_whitelist TEXT DEFAULT '[]',
ADD COLUMN IF NOT EXISTS ip_whitelist_enabled TINYINT(1) DEFAULT 0;

-- Add index for faster queries on whitelist-enabled sellers
CREATE INDEX IF NOT EXISTS idx_sellers_ip_whitelist_enabled
ON sellers(ip_whitelist_enabled);
