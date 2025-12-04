<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = db();

    echo "Adicionando coluna account_identifier...\n\n";

    // Check if column already exists
    $sql = "
    SELECT COUNT(*) as count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'acquirer_accounts'
    AND COLUMN_NAME = 'account_identifier'
    ";
    $result = $db->query($sql)->fetch();

    if ($result['count'] > 0) {
        echo "⚠ Coluna account_identifier já existe\n";
        exit;
    }

    // Add account_identifier column
    echo "1. Adicionando coluna account_identifier...\n";
    $sql = "ALTER TABLE acquirer_accounts
            ADD COLUMN account_identifier VARCHAR(255) NULL AFTER name";
    $db->exec($sql);
    echo "✓ Coluna adicionada\n\n";

    // Populate existing accounts
    echo "2. Populando dados existentes...\n";
    $sql = "UPDATE acquirer_accounts
            SET account_identifier = CONCAT('ACC-', LPAD(id, 6, '0'))
            WHERE account_identifier IS NULL";
    $affected = $db->exec($sql);
    echo "✓ {$affected} contas atualizadas\n\n";

    // Add unique constraint
    echo "3. Adicionando constraint de unicidade...\n";
    $sql = "ALTER TABLE acquirer_accounts
            ADD UNIQUE KEY unique_account_identifier (acquirer_id, account_identifier)";
    $db->exec($sql);
    echo "✓ Constraint adicionada\n\n";

    // Show updated accounts
    echo "4. Contas atualizadas:\n";
    $accounts = $db->query("
        SELECT aa.id, aa.name, aa.account_identifier, aa.merchant_id, a.name as acquirer_name
        FROM acquirer_accounts aa
        JOIN acquirers a ON a.id = aa.acquirer_id
        ORDER BY aa.id
    ")->fetchAll();

    foreach ($accounts as $account) {
        echo "  - [{$account['id']}] {$account['acquirer_name']}: {$account['name']}\n";
        echo "      account_identifier: {$account['account_identifier']}\n";
        echo "      merchant_id (x-withdraw-key): " . ($account['merchant_id'] ?: 'N/A') . "\n\n";
    }

    echo "✅ Migration aplicada com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Detalhes: " . $e->getTraceAsString() . "\n";
}
