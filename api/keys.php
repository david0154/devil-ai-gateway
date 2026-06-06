<?php
require_once __DIR__ . '/config.php';

$body = getJsonBody();
$name = trim($body['client_name'] ?? '');
$email = trim($body['client_email'] ?? '');
$appName = trim($body['app_name'] ?? '');
$amount = floatval($body['subscription_amount_inr'] ?? 0);
$planName = trim($body['plan_name'] ?? 'Starter');
$speedTier = strtolower(trim($body['speed_tier'] ?? 'normal'));
$limitPerDay = intval($body['limit_per_day'] ?? 100);
$validDays = intval($body['valid_days'] ?? 30);
$rpmLimit = intval($body['rpm_limit'] ?? 0);
$expiresAt = trim($body['expires_at'] ?? '');

if ($name === '' || $email === '' || $appName === '') {
    jsonResponse(['error' => 'client_name, client_email and app_name are required'], 400);
}

$tier = speedTierConfig($speedTier);
if ($rpmLimit <= 0) $rpmLimit = $tier['rpm'];
$timeout = $tier['timeout'];
if ($expiresAt === '') $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(1, $validDays) . ' days'));

$key = generateApiKey();

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO api_keys (name, email, client_name, client_email, app_name, api_key, subscription_amount_inr, currency, plan_name, speed_tier, rpm_limit, timeout_seconds, limit_per_day, expires_at, last_reset, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 1)");
    $stmt->execute([$name, $email, $name, $email, $appName, $key, $amount, DEFAULT_CURRENCY, $planName, $speedTier, $rpmLimit, $timeout, $limitPerDay, $expiresAt]);

    jsonResponse([
        'message' => 'API key created successfully',
        'api_key' => $key,
        'client' => [
            'client_name' => $name,
            'client_email' => $email,
            'app_name' => $appName
        ],
        'subscription' => [
            'plan_name' => $planName,
            'subscription_amount_inr' => $amount,
            'speed_tier' => $speedTier,
            'rpm_limit' => $rpmLimit,
            'limit_per_day' => $limitPerDay,
            'expires_at' => $expiresAt
        ],
        'docs_url' => GATEWAY_DOMAIN . '/docs/',
        'api_base_url' => GATEWAY_DOMAIN . '/api/v1',
        'validate_url' => GATEWAY_DOMAIN . '/api/v1/keys/validate'
    ], 201);
} catch (Exception $e) {
    jsonResponse(['error' => 'Could not create key: ' . $e->getMessage()], 500);
}
