<?php

require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Log.php';

class AuthService {
    private $sellerModel;
    private $logModel;

    public function __construct() {
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
    }

    public function authenticateApiRequest() {
        $apiKey = $this->getApiKeyFromHeaders();

        if (!$apiKey) {
            $this->logModel->warning('auth', 'Missing API Key', ['ip' => getClientIp()]);
            errorResponse('API Key is required', 401);
        }

        $seller = $this->sellerModel->findByApiKey($apiKey);

        if (!$seller) {
            $this->logModel->warning('auth', 'Invalid API Key', ['api_key' => $apiKey, 'ip' => getClientIp()]);
            errorResponse('Invalid API Key', 401);
        }

        if ($seller['status'] !== 'active') {
            $this->logModel->warning('auth', 'Inactive seller attempted access', [
                'seller_id' => $seller['id'],
                'status' => $seller['status']
            ]);
            errorResponse('Seller account is not active', 403);
        }

        $signature = $this->getSignatureFromHeaders();
        $body = file_get_contents('php://input');

        if ($signature && $body) {
            if (!verifyHmacSignature($body, $signature, $seller['api_secret'])) {
                $this->logModel->warning('auth', 'Invalid HMAC signature', [
                    'seller_id' => $seller['id'],
                    'ip' => getClientIp()
                ]);
                errorResponse('Invalid signature', 401);
            }
        }

        return $seller;
    }

    private function getApiKeyFromHeaders() {
        $headers = getallheaders();

        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }

        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function getSignatureFromHeaders() {
        $headers = getallheaders();
        return $headers['X-Signature'] ?? null;
    }

    public function checkRateLimit($identifier, $endpoint) {
        $db = db();

        $windowStart = date('Y-m-d H:i:00');
        $windowEnd = date('Y-m-d H:i:59', strtotime($windowStart) + API_RATE_WINDOW);

        $stmt = $db->prepare("
            SELECT requests FROM rate_limits
            WHERE identifier = ? AND endpoint = ? AND window_start = ?
        ");

        $stmt->execute([$identifier, $endpoint, $windowStart]);
        $result = $stmt->fetch();

        if ($result) {
            if ($result['requests'] >= API_RATE_LIMIT) {
                $this->logModel->warning('rate_limit', 'Rate limit exceeded', [
                    'identifier' => $identifier,
                    'endpoint' => $endpoint,
                    'requests' => $result['requests']
                ]);
                errorResponse('Rate limit exceeded. Try again later.', 429);
            }

            $stmt = $db->prepare("
                UPDATE rate_limits
                SET requests = requests + 1
                WHERE identifier = ? AND endpoint = ? AND window_start = ?
            ");

            $stmt->execute([$identifier, $endpoint, $windowStart]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO rate_limits (identifier, endpoint, requests, window_start, window_end)
                VALUES (?, ?, 1, ?, ?)
            ");

            $stmt->execute([$identifier, $endpoint, $windowStart, $windowEnd]);
        }

        $db->prepare("DELETE FROM rate_limits WHERE window_end < NOW()")->execute();

        return true;
    }
}
