<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = db();

    $result = [];

    // 1. Structure of acquirer_accounts
    $stmt = $db->query("DESCRIBE acquirer_accounts");
    $result['table_structure'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Check acquirers
    $stmt = $db->query("SELECT id, name, code, status FROM acquirers");
    $result['acquirers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Check acquirer_accounts (all columns)
    $stmt = $db->query("SELECT * FROM acquirer_accounts");
    $result['acquirer_accounts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Try the actual query
    $stmt = $db->query("
        SELECT aa.id, aa.name as account_name, aa.merchant_id,
               a.name as acquirer_name, a.code as acquirer_code, a.status as acquirer_status
        FROM acquirer_accounts aa
        JOIN acquirers a ON a.id = aa.acquirer_id
        WHERE aa.is_active = 1
        ORDER BY a.name, aa.name
    ");
    $result['available_accounts_all'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Try with status filter
    $stmt = $db->query("
        SELECT aa.id, aa.name as account_name,
               a.name as acquirer_name, a.code as acquirer_code, a.status as acquirer_status
        FROM acquirer_accounts aa
        JOIN acquirers a ON a.id = aa.acquirer_id
        WHERE aa.is_active = 1 AND a.status = 'active'
        ORDER BY a.name, aa.name
    ");
    $result['available_accounts_active'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
