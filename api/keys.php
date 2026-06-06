<?php
// ============================================
// Devil AI Gateway — API Key Generation
// POST /api/v1/keys/create
// ============================================

require_once __DIR__ . '/config.php';

$body  = json_decode(file_get_contents('php://input'), true);
$name  = trim($body['name']  ?? '');
$email = trim($body['email'] ?? '');
$limit = intval($body['limit_per_day'] ?? 100);

if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'name and email are required']);
    exit;
}

// Generate unique key
$key = 'dk_live_' . bin2hex(random_bytes(16));

try {
    $db = getDB();
    // Check if email already has a key
    $stmt = $db->prepare("SELECT api_key FROM api_keys WHERE email=?");
    $stmt->execute([$email]);
    if ($existing = $stmt->fetch()) {
        echo json_encode([
            'message'  => 'Key already exists for this email',
            'api_key'  => $existing['api_key'],
            'docs_url' => GATEWAY_DOMAIN . '/docs/'
        ]);
        exit;
    }
    $db->prepare("
        INSERT INTO api_keys (name, email, api_key, limit_per_day, last_reset)
        VALUES (?, ?, ?, ?, CURDATE())
    ")->execute([$name, $email, $key, $limit]);

    echo json_encode([
        'message'      => 'API key created successfully',
        'api_key'      => $key,
        'limit_per_day'=> $limit,
        'docs_url'     => GATEWAY_DOMAIN . '/docs/',
        'endpoint'     => GATEWAY_DOMAIN . '/api/v1/chat'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create key: ' . $e->getMessage()]);
}
