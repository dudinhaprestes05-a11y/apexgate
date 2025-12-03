<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/WebhookQueue.php';
require_once __DIR__ . '/../services/WebhookService.php';
require_once __DIR__ . '/../models/Log.php';

$logModel = new Log();
$webhookQueue = new WebhookQueue();
$webhookService = new WebhookService();

$logModel->info('worker', 'Retry failed callbacks worker started');

try {
    $failedWebhooks = $webhookQueue->getPendingWebhooks(50);

    $retried = 0;
    $success = 0;
    $failed = 0;

    foreach ($failedWebhooks as $webhook) {
        if ($webhook['attempts'] >= $webhook['max_attempts']) {
            continue;
        }

        $retried++;

        if ($webhookService->sendWebhook($webhook)) {
            $success++;
        } else {
            $failed++;
        }

        usleep(100000);
    }

    $logModel->info('worker', 'Retry failed callbacks worker completed', [
        'retried' => $retried,
        'success' => $success,
        'failed' => $failed
    ]);

    echo "Retried: {$retried}, Success: {$success}, Failed: {$failed}\n";

} catch (Exception $e) {
    $logModel->error('worker', 'Retry failed callbacks worker error', [
        'error' => $e->getMessage()
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
