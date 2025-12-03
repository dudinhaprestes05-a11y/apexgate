<?php
require_once __DIR__ . '/app/config/database.php';

if (!isset($argv[1])) {
    die("Uso: php debug_regenerate.php <seller_email>\n");
}

$email = $argv[1];
$db = db();

// Buscar seller
$stmt = $db->prepare("SELECT id, name, email, api_key, api_secret, status FROM sellers WHERE email = ?");
$stmt->execute([$email]);
$seller = $stmt->fetch();

if (!$seller) {
    die("Seller não encontrado!\n");
}

echo "=== SELLER ANTES DA REGENERAÇÃO ===\n";
echo "ID: {$seller['id']}\n";
echo "Nome: {$seller['name']}\n";
echo "Email: {$seller['email']}\n";
echo "Status: {$seller['status']}\n";
echo "API Key: {$seller['api_key']}\n";
echo "API Secret (hash no DB): {$seller['api_secret']}\n";
echo "Tamanho do hash: " . strlen($seller['api_secret']) . " caracteres\n\n";

// Simular regeneração
echo "=== SIMULANDO REGENERAÇÃO ===\n";
$newApiKey = 'sk_live_' . bin2hex(random_bytes(32));
$newApiSecret = bin2hex(random_bytes(32));
$hashedSecret = hash('sha256', $newApiSecret);

echo "Novo API Key gerado: $newApiKey\n";
echo "Novo API Secret (plain): $newApiSecret\n";
echo "Novo API Secret (hash SHA256): $hashedSecret\n";
echo "Tamanho do secret plain: " . strlen($newApiSecret) . " caracteres\n";
echo "Tamanho do secret hash: " . strlen($hashedSecret) . " caracteres\n\n";

// Perguntar se quer atualizar
echo "Deseja REALMENTE atualizar as credenciais? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if ($line !== 'yes') {
    die("Operação cancelada.\n");
}

// Atualizar
$stmt = $db->prepare("
    UPDATE sellers
    SET api_key = ?, api_secret = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([$newApiKey, $hashedSecret, $seller['id']]);

echo "\n=== CREDENCIAIS ATUALIZADAS ===\n";

// Verificar o que foi salvo
$stmt = $db->prepare("SELECT api_key, api_secret FROM sellers WHERE id = ?");
$stmt->execute([$seller['id']]);
$updated = $stmt->fetch();

echo "API Key no DB: {$updated['api_key']}\n";
echo "API Secret no DB: {$updated['api_secret']}\n";
echo "Tamanho do secret no DB: " . strlen($updated['api_secret']) . " caracteres\n\n";

// Verificar se o hash confere
$testHash = hash('sha256', $newApiSecret);
$hashMatches = ($testHash === $updated['api_secret']);

echo "=== VALIDAÇÃO ===\n";
echo "Hash do secret plain: $testHash\n";
echo "Hash no banco: {$updated['api_secret']}\n";
echo "Hashes coincidem: " . ($hashMatches ? 'SIM ✓' : 'NÃO ✗') . "\n\n";

if ($hashMatches) {
    echo "✓ SUCESSO! As credenciais foram salvas corretamente.\n";
    echo "\nGuarde estas credenciais (o secret plain não será mais mostrado):\n";
    echo "API Key: $newApiKey\n";
    echo "API Secret: $newApiSecret\n";
} else {
    echo "✗ ERRO! O hash não confere com o banco de dados!\n";
}
