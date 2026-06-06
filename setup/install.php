<?php
// ============================================
// Devil AI Gateway — Web-Based DB Installer
// Visit: your-domain.com/setup/install.php
// ============================================

require_once __DIR__ . '/../api/config.php';

$status = [];
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        $db->exec("
            CREATE TABLE IF NOT EXISTS api_keys (
              id INT AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(100),
              email VARCHAR(255),
              api_key VARCHAR(64) UNIQUE NOT NULL,
              requests_today INT DEFAULT 0,
              total_requests INT DEFAULT 0,
              limit_per_day INT DEFAULT 100,
              is_active TINYINT DEFAULT 1,
              last_reset DATE,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $status[] = ['ok', 'Table api_keys created'];

        $db->exec("
            CREATE TABLE IF NOT EXISTS request_logs (
              id INT AUTO_INCREMENT PRIMARY KEY,
              api_key VARCHAR(64),
              model VARCHAR(50),
              prompt TEXT,
              tools_used VARCHAR(255),
              response_time_ms INT,
              status VARCHAR(20),
              ip_address VARCHAR(45),
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $status[] = ['ok', 'Table request_logs created'];

        $db->exec("
            INSERT IGNORE INTO api_keys (name, email, api_key, limit_per_day)
            VALUES ('Admin Test Key', 'admin@devilone.in', 'dk_live_admin_test_key_001', 99999)
        ");
        $status[] = ['ok', 'Default admin key inserted: dk_live_admin_test_key_001'];

    } catch (Exception $e) {
        $status[] = ['error', $e->getMessage()];
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Devil AI Gateway Installer</title>
<style>
body{font-family:monospace;background:#0a0a0a;color:#00ff88;padding:30px;}
h1{color:#ff4444;} .ok{color:#00ff88;} .error{color:#ff4444;}
.btn{background:#ff4444;color:#fff;border:none;padding:12px 30px;font-size:16px;cursor:pointer;border-radius:5px;}
</style></head>
<body>
<h1>😈 Devil AI Gateway — Web Installer</h1>
<p>DB: <b><?= DB_HOST ?>/<?= DB_NAME ?></b></p>
<?php if (!empty($status)): ?>
    <?php foreach ($status as [$type, $msg]): ?>
        <p class="<?= $type ?>"><?= $type === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?></p>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <p>✅ <b>Installation complete!</b> <a href="../docs/" style="color:#00ff88">→ Go to Docs</a></p>
        <p style="color:#ffaa00">⚠️ Delete this file after installation for security!</p>
    <?php endif; ?>
<?php else: ?>
<form method="POST">
    <p>This will create required tables in your database.</p>
    <button class="btn" type="submit">🚀 Run Installation</button>
</form>
<?php endif; ?>
</body></html>
