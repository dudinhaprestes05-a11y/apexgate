<?php
/**
 * Script para aplicar a migration de remoção de daily_limit
 *
 * Remove os campos daily_limit, daily_used e daily_reset_at das tabelas
 * sellers e acquirers, pois não são mais necessários.
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    echo "==================================================\n";
    echo "  MIGRATION: Remover daily_limit                 \n";
    echo "==================================================\n\n";

    // Verificar quais campos existem antes da remoção
    echo "1. Verificando campos existentes...\n";

    $checkSellers = $db->query("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'sellers'
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME IN ('daily_limit', 'daily_used', 'daily_reset_at')
    ");
    $sellerColumns = $checkSellers->fetchAll(PDO::FETCH_COLUMN);

    $checkAcquirers = $db->query("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'acquirers'
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME IN ('daily_limit', 'daily_used', 'daily_reset_at')
    ");
    $acquirerColumns = $checkAcquirers->fetchAll(PDO::FETCH_COLUMN);

    echo "   Sellers: " . implode(', ', $sellerColumns ?: ['nenhum']) . "\n";
    echo "   Acquirers: " . implode(', ', $acquirerColumns ?: ['nenhum']) . "\n\n";

    if (empty($sellerColumns) && empty($acquirerColumns)) {
        echo "✓ Migration já foi aplicada anteriormente.\n";
        echo "  Não há campos para remover.\n\n";
        exit(0);
    }

    // Confirmar antes de executar
    echo "ATENÇÃO: Esta operação irá remover os campos permanentemente.\n";
    echo "Certifique-se de ter um backup do banco de dados.\n\n";
    echo "Deseja continuar? (s/N): ";

    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (strtolower(trim($line)) !== 's') {
        echo "Migration cancelada.\n";
        exit(0);
    }
    fclose($handle);

    echo "\n2. Aplicando migration...\n";

    $db->beginTransaction();

    // Remover campos de sellers
    if (!empty($sellerColumns)) {
        echo "   Removendo campos de 'sellers'...\n";

        foreach ($sellerColumns as $column) {
            try {
                $db->exec("ALTER TABLE sellers DROP COLUMN IF EXISTS $column");
                echo "   ✓ Campo 'sellers.$column' removido\n";
            } catch (PDOException $e) {
                echo "   ! Campo 'sellers.$column' não pôde ser removido: " . $e->getMessage() . "\n";
            }
        }
    }

    // Remover campos de acquirers
    if (!empty($acquirerColumns)) {
        echo "   Removendo campos de 'acquirers'...\n";

        foreach ($acquirerColumns as $column) {
            try {
                $db->exec("ALTER TABLE acquirers DROP COLUMN IF EXISTS $column");
                echo "   ✓ Campo 'acquirers.$column' removido\n";
            } catch (PDOException $e) {
                echo "   ! Campo 'acquirers.$column' não pôde ser removido: " . $e->getMessage() . "\n";
            }
        }
    }

    $db->commit();

    echo "\n3. Verificando resultado...\n";

    $checkAfterSellers = $db->query("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'sellers'
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME LIKE '%daily%'
    ");
    $remainingSellersColumns = $checkAfterSellers->fetchAll(PDO::FETCH_COLUMN);

    $checkAfterAcquirers = $db->query("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'acquirers'
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME LIKE '%daily%'
    ");
    $remainingAcquirersColumns = $checkAfterAcquirers->fetchAll(PDO::FETCH_COLUMN);

    echo "\n   Campos restantes com 'daily' no nome:\n";
    echo "   Sellers: " . implode(', ', $remainingSellersColumns ?: ['nenhum']) . "\n";
    echo "   Acquirers: " . implode(', ', $remainingAcquirersColumns ?: ['nenhum']) . "\n";

    echo "\n==================================================\n";
    echo "✓ Migration aplicada com sucesso!                \n";
    echo "==================================================\n\n";

    echo "Observações:\n";
    echo "- Os campos daily_limit, daily_used e daily_reset_at foram removidos\n";
    echo "- O campo cashout_daily_limit foi mantido (específico para cashout)\n";
    echo "- Limite diário agora é controlado apenas no cashout\n";
    echo "- Não há mais limite diário para cash-in\n\n";

    echo "Próximos passos:\n";
    echo "1. Verifique se o sistema está funcionando corretamente\n";
    echo "2. Teste transações de cash-in e cash-out\n";
    echo "3. Monitore os logs para qualquer erro\n\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    echo "\n✗ Erro ao aplicar migration:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
