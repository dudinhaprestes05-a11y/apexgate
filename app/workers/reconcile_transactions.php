<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/PixCashin.php';
require_once __DIR__ . '/../models/Log.php';

$pixCashinModel = new PixCashin();
$logModel = new Log();

$logModel->info('worker', 'Reconciliation worker started');

try {
    $expiredTransactions = $pixCashinModel->getExpiredTransactions(100);

    $count = 0;
    foreach ($expiredTransactions as $transaction) {
        $pixCashinModel->updateStatus($transaction['transaction_id'], 'expired');
        $count++;
    }

    $logModel->info('worker', 'Reconciliation worker completed', [
        'expired_transactions' => $count
    ]);

    echo "Expired transactions marked: {$count}\n";

} catch (Exception $e) {
    $logModel->error('worker', 'Reconciliation worker error', [
        'error' => $e->getMessage()
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
