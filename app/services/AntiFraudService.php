<?php

require_once __DIR__ . '/../models/PixCashin.php';
require_once __DIR__ . '/../models/Log.php';

class AntiFraudService {
    private $pixCashinModel;
    private $logModel;
    private $rules;

    public function __construct() {
        $this->pixCashinModel = new PixCashin();
        $this->logModel = new Log();

        $this->rules = [
            'max_amount_per_transaction' => 10000.00,
            'max_transactions_per_hour' => 10,
            'max_amount_per_hour' => 50000.00,
            'min_amount' => 1.00,
            'max_failed_attempts' => 5,
            'suspicious_amount_threshold' => 5000.00
        ];
    }

    public function analyzeTransaction($sellerId, $amount, $payerDocument = null) {
        $risks = [];
        $score = 0;

        if ($amount > $this->rules['max_amount_per_transaction']) {
            $risks[] = 'Amount exceeds maximum allowed per transaction';
            $score += 100;
        }

        if ($amount < $this->rules['min_amount']) {
            $risks[] = 'Amount below minimum allowed';
            $score += 50;
        }

        $hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));

        $recentTransactions = $this->pixCashinModel->where([
            'seller_id' => $sellerId,
        ], 'created_at DESC');

        $recentCount = 0;
        $recentAmount = 0;

        foreach ($recentTransactions as $tx) {
            if ($tx['created_at'] >= $hourAgo) {
                $recentCount++;
                $recentAmount += $tx['amount'];
            }
        }

        if ($recentCount >= $this->rules['max_transactions_per_hour']) {
            $risks[] = 'Too many transactions in the last hour';
            $score += 80;
        }

        if ($recentAmount + $amount > $this->rules['max_amount_per_hour']) {
            $risks[] = 'Total amount in last hour exceeds limit';
            $score += 80;
        }

        if ($amount >= $this->rules['suspicious_amount_threshold']) {
            $risks[] = 'High value transaction requires additional verification';
            $score += 40;
        }

        if ($payerDocument) {
            $duplicateCheck = $this->checkDuplicateDocument($sellerId, $payerDocument);
            if ($duplicateCheck['suspicious']) {
                $risks[] = $duplicateCheck['message'];
                $score += 60;
            }
        }

        $failedTransactions = array_filter($recentTransactions, function($tx) use ($hourAgo) {
            return $tx['status'] === 'failed' && $tx['created_at'] >= $hourAgo;
        });

        if (count($failedTransactions) >= $this->rules['max_failed_attempts']) {
            $risks[] = 'Multiple failed transactions detected';
            $score += 70;
        }

        $result = [
            'approved' => $score < 100,
            'score' => $score,
            'risks' => $risks,
            'level' => $this->getRiskLevel($score)
        ];

        $this->logModel->info('antifraud', 'Transaction analyzed', [
            'seller_id' => $sellerId,
            'amount' => $amount,
            'score' => $score,
            'approved' => $result['approved'],
            'risks' => $risks
        ]);

        return $result;
    }

    private function checkDuplicateDocument($sellerId, $document) {
        $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime('-5 minutes'));

        $stmt = db()->prepare("
            SELECT COUNT(*) as count
            FROM pix_cashin
            WHERE seller_id = ?
            AND payer_document = ?
            AND created_at >= ?
        ");

        $stmt->execute([$sellerId, $document, $fiveMinutesAgo]);
        $result = $stmt->fetch();

        if ($result['count'] > 3) {
            return [
                'suspicious' => true,
                'message' => 'Same payer document used multiple times recently'
            ];
        }

        return ['suspicious' => false];
    }

    private function getRiskLevel($score) {
        if ($score < 50) {
            return 'low';
        } elseif ($score < 100) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    public function validatePixKey($pixKey, $pixKeyType) {
        switch ($pixKeyType) {
            case 'cpf':
            case 'cnpj':
                return validateCpfCnpj($pixKey);

            case 'email':
                return validateEmail($pixKey);

            case 'phone':
                return preg_match('/^\+?[1-9]\d{1,14}$/', preg_replace('/[^0-9+]/', '', $pixKey));

            case 'random':
                return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $pixKey);

            default:
                return false;
        }
    }

    public function blockSeller($sellerId, $reason) {
        $seller = new Seller();
        $seller->update($sellerId, ['status' => 'blocked']);

        $this->logModel->critical('antifraud', 'Seller blocked', [
            'seller_id' => $sellerId,
            'reason' => $reason
        ]);

        return true;
    }
}
