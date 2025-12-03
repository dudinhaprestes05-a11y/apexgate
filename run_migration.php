<?php

require_once 'app/config/database.php';

$db = Database::getInstance()->getConnection();
$sql = file_get_contents('sql/add_seller_controls.sql');

try {
    $db->exec($sql);
    echo "✓ Migração executada com sucesso!\n";
    echo "Campos adicionados:\n";
    echo "  - cashin_enabled\n";
    echo "  - cashout_enabled\n";
    echo "  - temporarily_blocked\n";
    echo "  - permanently_blocked\n";
    echo "  - blocked_reason\n";
    echo "  - blocked_at\n";
    echo "  - blocked_by\n";
    echo "  - balance_retention\n";
    echo "  - revenue_retention_percentage\n";
    echo "  - retention_reason\n";
    echo "  - retention_started_at\n";
    echo "  - retention_started_by\n";
} catch (PDOException $e) {
    echo "✗ Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}
