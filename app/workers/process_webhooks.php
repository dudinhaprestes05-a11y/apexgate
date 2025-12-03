<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/WebhookService.php';
require_once __DIR__ . '/../models/Log.php';

$logModel = new Log();
$webhookService = new WebhookService();

$logModel->info('worker', 'Webhook worker started');

try {
    $results = $webhookService->processPendingWebhooks(50);

    $logModel->info('worker', 'Webhook worker completed', [
        'processed' => $results['processed'],
        'success' => $results['success'],
        'failed' => $results['failed']
    ]);

    echo "Processed: {$results['processed']}, Success: {$results['success']}, Failed: {$results['failed']}\n";

} catch (Exception $e) {
    $logModel->error('worker', 'Webhook worker error', [
        'error' => $e->getMessage()
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
