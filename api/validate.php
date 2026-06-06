<?php
require_once __DIR__ . '/config.php';

$body = getJsonBody();
$apiKey = trim($body['api_key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? ''));
if ($apiKey === '') {
    jsonResponse(['error' => 'api_key or X-API-Key header is required'], 400);
}

try {
    $db = getDB();
    ensureRequestCountersReset($db);
    $stmt = $db->prepare("SELECT * FROM api_keys WHERE api_key = ? LIMIT 1");
    $stmt->execute([$apiKey]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) jsonResponse(['valid' => false, 'error' => 'API key not found'], 404);

    $expired = !empty($row['expires_at']) && strtotime($row['expires_at']) < time();
    $minuteBucket = currentMinuteBucket();
    $minuteRemaining = max(0, intval($row['rpm_limit']) - intval((($row['minute_bucket'] ?? '') === $minuteBucket) ? $row['requests_this_minute'] : 0));

    jsonResponse([
        'valid' => intval($row['is_active']) === 1 && !$expired,
        'api_key' => $row['api_key'],
        'client' => [
            'client_name' => $row['client_name'],
            'client_email' => $row['client_email'],
            'app_name' => $row['app_name']
        ],
        'subscription' => [
            'plan_name' => $row['plan_name'],
            'subscription_amount_inr' => floatval($row['subscription_amount_inr']),
            'currency' => $row['currency'],
            'speed_tier' => $row['speed_tier'],
            'rpm_limit' => intval($row['rpm_limit']),
            'limit_per_day' => intval($row['limit_per_day']),
            'expires_at' => $row['expires_at'],
            'is_active' => intval($row['is_active']) === 1,
            'is_expired' => $expired
        ],
        'usage' => [
            'requests_today' => intval($row['requests_today']),
            'daily_remaining' => max(0, intval($row['limit_per_day']) - intval($row['requests_today'])),
            'minute_remaining' => $minuteRemaining,
            'total_requests' => intval($row['total_requests']),
            'last_used_at' => $row['last_used_at']
        ],
        'docs_url' => GATEWAY_DOMAIN . '/docs/',
        'api_base_url' => GATEWAY_DOMAIN . '/api/v1'
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Validation failed: ' . $e->getMessage()], 500);
}
