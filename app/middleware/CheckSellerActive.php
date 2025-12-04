<?php

require_once __DIR__ . '/CheckAuth.php';
require_once __DIR__ . '/../models/Seller.php';

class CheckSellerActive {
    public static function handle() {
        CheckAuth::handle();

        if (!CheckAuth::isSeller()) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }

        $sellerId = CheckAuth::sellerId();

        if (!$sellerId) {
            header('Location: /login');
            exit;
        }

        $sellerModel = new Seller();
        $seller = $sellerModel->find($sellerId);

        if (!$seller) {
            header('Location: /login');
            exit;
        }

        if ($seller['status'] === 'pending') {
            $allowedUris = ['/seller/documents', '/seller/personal-info', '/seller/personal-info/save', '/seller/profile', '/logout'];
            $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            if (!in_array($currentUri, $allowedUris)) {
                if (!$seller['personal_info_completed']) {
                    header('Location: /seller/personal-info');
                } else {
                    header('Location: /seller/documents');
                }
                exit;
            }
        }

        if ($seller['status'] === 'blocked') {
            http_response_code(403);
            echo "Sua conta está bloqueada. Entre em contato com o suporte.";
            exit;
        }

        if ($seller['status'] === 'rejected') {
            http_response_code(403);
            echo "Sua conta foi rejeitada. Entre em contato com o suporte.";
            exit;
        }

        return true;
    }
}
