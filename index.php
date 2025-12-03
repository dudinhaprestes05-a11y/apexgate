<?php

session_start();

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/helpers.php';

if (MAINTENANCE_MODE && !in_array(getClientIp(), ALLOWED_IPS)) {
    http_response_code(503);
    echo "System under maintenance. Please try again later.";
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

try {
    if (preg_match('#^/api/pix/create$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->create();
    }
    elseif (preg_match('#^/api/pix/consult$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->consult();
    }
    elseif (preg_match('#^/api/pix/list$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->listTransactions();
    }
    elseif (preg_match('#^/api/cashout/create$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->create();
    }
    elseif (preg_match('#^/api/cashout/consult$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->consult();
    }
    elseif (preg_match('#^/api/cashout/list$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->listTransactions();
    }
    elseif (preg_match('#^/api/webhook/acquirer$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/WebhookController.php';
        $controller = new WebhookController();
        $controller->receiveFromAcquirer();
    }
    elseif ($uri === '/' || $uri === '') {
        echo json_encode([
            'app' => APP_NAME,
            'version' => APP_VERSION,
            'status' => 'online',
            'timestamp' => date('c')
        ]);
    }
    else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $uri
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);

    $response = [
        'error' => 'Internal server error'
    ];

    if (APP_ENV === 'development') {
        $response['message'] = $e->getMessage();
        $response['trace'] = $e->getTraceAsString();
    }

    echo json_encode($response);

    require_once __DIR__ . '/app/models/Log.php';
    $logModel = new Log();
    $logModel->critical('system', 'Uncaught exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
