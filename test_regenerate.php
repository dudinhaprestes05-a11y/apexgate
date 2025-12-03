<?php
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/helpers.php';

header('Content-Type: text/plain; charset=utf-8');

// Verificar se já tem email na sessão ou nos parâmetros
$email = $_GET['email'] ?? $_SESSION['test_email'] ?? null;

if (!$email) {
    die("Uso: ?email=seu@email.com\n");
}

$_SESSION['test_email'] = $email;
$db = db();

// Buscar seller
$stmt = $db->prepare("SELECT id, name, email, api_key, api_secret, status FROM sellers WHERE email = ?");
$stmt->execute([$email]);
$seller = $stmt->fetch();

if (!$seller) {
    die("Seller não encontrado com email: $email\n");
}

echo "=== SELLER ATUAL ===\n";
echo "ID: {$seller['id']}\n";
echo "Nome: {$seller['name']}\n";
echo "Email: {$seller['email']}\n";
echo "Status: {$seller['status']}\n";
echo "API Key: {$seller['api_key']}\n";
echo "API Secret (hash no DB): " . substr($seller['api_secret'], 0, 16) . "...\n";
echo "Tamanho do hash no DB: " . strlen($seller['api_secret']) . " caracteres\n\n";

// Se tiver o parâmetro test=1, simular regeneração
if (isset($_GET['test'])) {
    echo "=== SIMULANDO REGENERAÇÃO (SEM SALVAR) ===\n";
    $newApiKey = 'sk_live_' . bin2hex(random_bytes(32));
    $newApiSecret = bin2hex(random_bytes(32));
    $hashedSecret = hash('sha256', $newApiSecret);

    echo "Novo API Key: $newApiKey\n";
    echo "Novo API Secret (plain): $newApiSecret\n";
    echo "Novo API Secret (hash): " . substr($hashedSecret, 0, 16) . "...\n";
    echo "Tamanho do plain: " . strlen($newApiSecret) . " caracteres\n";
    echo "Tamanho do hash: " . strlen($hashedSecret) . " caracteres\n\n";

    echo "O que seria salvo no DB: hash SHA256 ($hashedSecret)\n";
    echo "O que seria mostrado ao usuário: plain text ($newApiSecret)\n\n";

    echo "Acesse ?email=$email&regenerate=1 para REALMENTE regenerar\n";
}

// Se tiver o parâmetro regenerate=1, realmente regenerar
if (isset($_GET['regenerate'])) {
    echo "=== REGENERANDO CREDENCIAIS ===\n";
    $newApiKey = 'sk_live_' . bin2hex(random_bytes(32));
    $newApiSecret = bin2hex(random_bytes(32));
    $hashedSecret = hash('sha256', $newApiSecret);

    // Salvar nova credencial
    $stmt = $db->prepare("
        UPDATE sellers
        SET api_key = ?, api_secret = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $result = $stmt->execute([$newApiKey, $hashedSecret, $seller['id']]);

    if ($result) {
        echo "✓ Credenciais atualizadas com sucesso!\n\n";

        // Buscar novamente para confirmar
        $stmt = $db->prepare("SELECT api_key, api_secret FROM sellers WHERE id = ?");
        $stmt->execute([$seller['id']]);
        $updated = $stmt->fetch();

        echo "=== VERIFICAÇÃO ===\n";
        echo "API Key salvo no DB: {$updated['api_key']}\n";
        echo "API Secret salvo no DB: " . substr($updated['api_secret'], 0, 16) . "...\n";
        echo "Tamanho no DB: " . strlen($updated['api_secret']) . " caracteres\n\n";

        // Testar se o hash confere
        $testHash = hash('sha256', $newApiSecret);
        $hashMatches = ($testHash === $updated['api_secret']);

        echo "Hash calculado do plain: " . substr($testHash, 0, 16) . "...\n";
        echo "Hash no banco: " . substr($updated['api_secret'], 0, 16) . "...\n";
        echo "Hashes coincidem: " . ($hashMatches ? 'SIM ✓' : 'NÃO ✗') . "\n\n";

        if ($hashMatches) {
            echo "=== SUCESSO! ===\n";
            echo "GUARDE ESTAS CREDENCIAIS:\n\n";
            echo "API Key: $newApiKey\n";
            echo "API Secret: $newApiSecret\n\n";
            echo "O secret acima NÃO será mais mostrado!\n";
            echo "No banco está salvo o hash SHA256 dele.\n";
        } else {
            echo "✗ ERRO! O hash não confere!\n";
        }
    } else {
        echo "✗ Erro ao atualizar credenciais\n";
    }
}

if (!isset($_GET['test']) && !isset($_GET['regenerate'])) {
    echo "\nOpções:\n";
    echo "- ?email=$email&test=1 - Simular regeneração (sem salvar)\n";
    echo "- ?email=$email&regenerate=1 - REALMENTE regenerar credenciais\n";
}
