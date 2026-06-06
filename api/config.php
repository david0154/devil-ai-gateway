<?php
// ============================================
// Devil AI Gateway — Config Loader
// ============================================

// Load .env file
function loadEnv($file = __DIR__ . '/../.env') {
    if (!file_exists($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $val] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

loadEnv();

define('OLLAMA_URL',      getenv('OLLAMA_URL')      ?: 'https://aiapi.devilpvt.in');
define('DB_HOST',         getenv('DB_HOST')          ?: 'localhost');
define('DB_NAME',         getenv('DB_NAME')          ?: 'devil_ai_gateway');
define('DB_USER',         getenv('DB_USER')          ?: 'root');
define('DB_PASS',         getenv('DB_PASS')          ?: '');
define('ADMIN_PASSWORD',  getenv('ADMIN_PASSWORD')   ?: 'admin123');
define('GATEWAY_DOMAIN',  getenv('GATEWAY_DOMAIN')  ?: 'https://api.devilone.in');

// DB Connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
