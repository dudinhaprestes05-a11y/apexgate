<?php

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Log.php';

class SplitService {
    private $db;
    private $sellerModel;
    private $logModel;

    public function __construct() {
        $this->db = db();
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
    }

    public function createSplits($cashinId, $totalAmount, $splits) {
        $this->db->beginTransaction();

        try {
            $totalSplitAmount = 0;

            foreach ($splits as $split) {
                $sellerId = $split['seller_id'];
                $amount = isset($split['amount']) ? $split['amount'] : null;
                $percentage = isset($split['percentage']) ? $split['percentage'] : null;

                if ($amount === null && $percentage === null) {
                    throw new Exception("Split must have either amount or percentage");
                }

                if ($amount === null) {
                    $amount = ($totalAmount * $percentage) / 100;
                }

                $totalSplitAmount += $amount;

                $stmt = $this->db->prepare("
                    INSERT INTO splits (cashin_id, seller_id, amount, percentage, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");

                $stmt->execute([$cashinId, $sellerId, $amount, $percentage]);

                $this->logModel->info('split', 'Split created', [
                    'cashin_id' => $cashinId,
                    'seller_id' => $sellerId,
                    'amount' => $amount,
                    'percentage' => $percentage
                ]);
            }

            if ($totalSplitAmount > $totalAmount) {
                throw new Exception("Total split amount exceeds transaction amount");
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();

            $this->logModel->error('split', 'Failed to create splits', [
                'cashin_id' => $cashinId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function processSplits($cashinId) {
        $stmt = $this->db->prepare("SELECT * FROM splits WHERE cashin_id = ? AND status = 'pending'");
        $stmt->execute([$cashinId]);
        $splits = $stmt->fetchAll();

        if (empty($splits)) {
            return true;
        }

        $this->db->beginTransaction();

        try {
            foreach ($splits as $split) {
                $this->sellerModel->updateBalance($split['seller_id'], $split['amount']);

                $updateStmt = $this->db->prepare("
                    UPDATE splits
                    SET status = 'processed', processed_at = NOW()
                    WHERE id = ?
                ");

                $updateStmt->execute([$split['id']]);

                $this->logModel->info('split', 'Split processed', [
                    'split_id' => $split['id'],
                    'cashin_id' => $cashinId,
                    'seller_id' => $split['seller_id'],
                    'amount' => $split['amount']
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();

            $this->logModel->error('split', 'Failed to process splits', [
                'cashin_id' => $cashinId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function getSplitsByCashin($cashinId) {
        $stmt = $this->db->prepare("
            SELECT s.*, sel.name as seller_name, sel.email as seller_email
            FROM splits s
            LEFT JOIN sellers sel ON s.seller_id = sel.id
            WHERE s.cashin_id = ?
        ");

        $stmt->execute([$cashinId]);
        return $stmt->fetchAll();
    }

    public function getSplitsBySeller($sellerId, $status = null) {
        $sql = "
            SELECT s.*, c.transaction_id, c.amount as transaction_amount, c.status as transaction_status
            FROM splits s
            LEFT JOIN pix_cashin c ON s.cashin_id = c.id
            WHERE s.seller_id = ?
        ";

        $params = [$sellerId];

        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY s.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function validateSplits($amount, $splits) {
        $totalSplitAmount = 0;

        foreach ($splits as $split) {
            if (!isset($split['seller_id'])) {
                return ['valid' => false, 'error' => 'seller_id is required for each split'];
            }

            $seller = $this->sellerModel->find($split['seller_id']);
            if (!$seller) {
                return ['valid' => false, 'error' => "Seller {$split['seller_id']} not found"];
            }

            if ($seller['status'] !== 'active') {
                return ['valid' => false, 'error' => "Seller {$split['seller_id']} is not active"];
            }

            $splitAmount = isset($split['amount']) ? $split['amount'] : null;
            $splitPercentage = isset($split['percentage']) ? $split['percentage'] : null;

            if ($splitAmount === null && $splitPercentage === null) {
                return ['valid' => false, 'error' => 'Each split must have either amount or percentage'];
            }

            if ($splitAmount !== null && $splitAmount <= 0) {
                return ['valid' => false, 'error' => 'Split amount must be greater than zero'];
            }

            if ($splitPercentage !== null && ($splitPercentage <= 0 || $splitPercentage > 100)) {
                return ['valid' => false, 'error' => 'Split percentage must be between 0 and 100'];
            }

            if ($splitAmount === null) {
                $splitAmount = ($amount * $splitPercentage) / 100;
            }

            $totalSplitAmount += $splitAmount;
        }

        if ($totalSplitAmount > $amount) {
            return ['valid' => false, 'error' => 'Total split amount exceeds transaction amount'];
        }

        return ['valid' => true];
    }
}
