<?php
// ============================================
// Devil AI Gateway — Config Loader + Helpers
// ============================================

function loadEnv($file = __DIR__ . '/../.env') {
    if (!file_exists($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}
loadEnv();

define('OLLAMA_URL',      getenv('OLLAMA_URL')      ?: 'https://aiapi.devilpvt.in');
define('DB_HOST',         getenv('DB_HOST')         ?: 'localhost');
define('DB_NAME',         getenv('DB_NAME')         ?: 'devil_ai_gateway');
define('DB_USER',         getenv('DB_USER')         ?: 'root');
define('DB_PASS',         getenv('DB_PASS')         ?: '');
define('ADMIN_PASSWORD',  getenv('ADMIN_PASSWORD')  ?: 'admin123');
define('GATEWAY_DOMAIN',  getenv('GATEWAY_DOMAIN')  ?: 'https://api.devilone.in');
define('DEFAULT_CURRENCY',getenv('DEFAULT_CURRENCY')?: 'INR');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function generateApiKey(): string {
    return 'dk_live_' . bin2hex(random_bytes(16));
}

function speedTierConfig(string $tier): array {
    $map = [
        'slow' =>   ['rpm' => 10,  'timeout' => 90, 'label' => 'Slow'],
        'normal' => ['rpm' => 30,  'timeout' => 60, 'label' => 'Normal'],
        'fast' =>   ['rpm' => 60,  'timeout' => 45, 'label' => 'Fast'],
        'ultra' =>  ['rpm' => 120, 'timeout' => 30, 'label' => 'Ultra'],
    ];
    return $map[$tier] ?? $map['normal'];
}

function ensureRequestCountersReset(PDO $db): void {
    $db->prepare("UPDATE api_keys SET requests_today = 0, last_reset = CURDATE() WHERE last_reset < CURDATE() OR last_reset IS NULL")->execute();
}

function currentMinuteBucket(): string {
    return date('Y-m-d H:i');
}

function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}
