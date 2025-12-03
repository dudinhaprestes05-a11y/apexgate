<?php

require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../services/WebhookService.php';
require_once __DIR__ . '/../../services/SplitService.php';

class WebhookController {
    private $acquirerModel;
    private $pixCashinModel;
    private $pixCashoutModel;
    private $sellerModel;
    private $logModel;
    private $webhookService;
    private $splitService;

    public function __construct() {
        $this->acquirerModel = new Acquirer();
        $this->pixCashinModel = new PixCashin();
        $this->pixCashoutModel = new PixCashout();
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
        $this->webhookService = new WebhookService();
        $this->splitService = new SplitService();
    }

    public function receiveFromAcquirer() {
        $acquirerCode = $_GET['acquirer'] ?? null;

        if (!$acquirerCode) {
            errorResponse('Acquirer code is required', 400);
        }

        $acquirer = $this->acquirerModel->findByCode($acquirerCode);

        if (!$acquirer) {
            errorResponse('Acquirer not found', 404);
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$data) {
            errorResponse('Invalid JSON payload', 400);
        }

        $headers = getAllHeadersCaseInsensitive();
        $signature = $headers['X-Signature'] ?? null;

        if ($signature && $acquirer['api_secret']) {
            if (!verifyHmacSignature($payload, $signature, $acquirer['api_secret'])) {
                $this->logModel->warning('webhook', 'Invalid signature from acquirer', [
                    'acquirer_id' => $acquirer['id'],
                    'ip' => getClientIp()
                ]);
                errorResponse('Invalid signature', 401);
            }
        }

        $callbackId = $this->logCallback($acquirer['id'], $data, $headers);

        $this->processAcquirerWebhook($acquirer, $data, $callbackId);

        successResponse(null, 'Webhook received successfully');
    }

    private function logCallback($acquirerId, $data, $headers) {
        $db = db();

        $stmt = $db->prepare("
            INSERT INTO callbacks_acquirers (acquirer_id, transaction_id, acquirer_transaction_id, payload, headers, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $acquirerId,
            $data['transaction_id'] ?? null,
            $data['acquirer_transaction_id'] ?? null,
            json_encode($data),
            json_encode($headers),
            getClientIp()
        ]);

        return $db->lastInsertId();
    }

    private function processAcquirerWebhook($acquirer, $data, $callbackId) {
        try {
            $transactionId = $data['transaction_id'] ?? null;
            $transactionType = $data['transaction_type'] ?? 'cashin';
            $status = $data['status'] ?? null;

            if (!$transactionId || !$status) {
                throw new Exception('Missing required fields: transaction_id or status');
            }

            if ($transactionType === 'cashin') {
                $this->processCashinWebhook($transactionId, $data);
            } elseif ($transactionType === 'cashout') {
                $this->processCashoutWebhook($transactionId, $data);
            } else {
                throw new Exception('Invalid transaction type');
            }

            $this->markCallbackProcessed($callbackId);

        } catch (Exception $e) {
            $this->markCallbackError($callbackId, $e->getMessage());

            $this->logModel->error('webhook', 'Failed to process acquirer webhook', [
                'acquirer_id' => $acquirer['id'],
                'callback_id' => $callbackId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processCashinWebhook($transactionId, $data) {
        $transaction = $this->pixCashinModel->findByTransactionId($transactionId);

        if (!$transaction) {
            throw new Exception("Transaction not found: {$transactionId}");
        }

        $updateData = [
            'status' => $data['status']
        ];

        if (isset($data['end_to_end_id'])) {
            $updateData['end_to_end_id'] = $data['end_to_end_id'];
        }

        if (isset($data['payer_name'])) {
            $updateData['payer_name'] = $data['payer_name'];
        }

        if (isset($data['payer_document'])) {
            $updateData['payer_document'] = sanitizeDocument($data['payer_document']);
        }

        if (isset($data['payer_bank'])) {
            $updateData['payer_bank'] = $data['payer_bank'];
        }

        if ($data['status'] === 'paid') {
            $this->sellerModel->updateBalance($transaction['seller_id'], $transaction['net_amount']);

            $this->splitService->processSplits($transaction['id']);
        }

        $this->pixCashinModel->updateStatus($transactionId, $data['status'], $updateData);

        $updatedTransaction = $this->pixCashinModel->findByTransactionId($transactionId);
        $this->webhookService->enqueueWebhook(
            $transaction['seller_id'],
            $transactionId,
            'cashin',
            $updatedTransaction
        );

        $this->logModel->info('webhook', 'Cashin webhook processed', [
            'transaction_id' => $transactionId,
            'status' => $data['status']
        ]);
    }

    private function processCashoutWebhook($transactionId, $data) {
        $transaction = $this->pixCashoutModel->findByTransactionId($transactionId);

        if (!$transaction) {
            throw new Exception("Transaction not found: {$transactionId}");
        }

        $updateData = [
            'status' => $data['status']
        ];

        if (isset($data['end_to_end_id'])) {
            $updateData['end_to_end_id'] = $data['end_to_end_id'];
        }

        if ($data['status'] === 'failed') {
            $this->sellerModel->updateBalance($transaction['seller_id'], $transaction['amount']);

            if (isset($data['error_message'])) {
                $updateData['error_message'] = $data['error_message'];
            }
        }

        $this->pixCashoutModel->updateStatus($transactionId, $data['status'], $updateData);

        $updatedTransaction = $this->pixCashoutModel->findByTransactionId($transactionId);
        $this->webhookService->enqueueWebhook(
            $transaction['seller_id'],
            $transactionId,
            'cashout',
            $updatedTransaction
        );

        $this->logModel->info('webhook', 'Cashout webhook processed', [
            'transaction_id' => $transactionId,
            'status' => $data['status']
        ]);
    }

    private function markCallbackProcessed($callbackId) {
        $db = db();
        $stmt = $db->prepare("UPDATE callbacks_acquirers SET status = 'processed', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$callbackId]);
    }

    private function markCallbackError($callbackId, $error) {
        $db = db();
        $stmt = $db->prepare("UPDATE callbacks_acquirers SET status = 'error', error_message = ? WHERE id = ?");
        $stmt->execute([$error, $callbackId]);
    }
}
