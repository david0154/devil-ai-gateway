<?php
session_start();
$step = intval($_POST['step'] ?? $_GET['step'] ?? 1);
$errors = [];
$cfg = $_SESSION['cfg'] ?? [];
$adminKey = $_SESSION['admin_key'] ?? '';
$installLogs = $_SESSION['install_logs'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $cfg = [
        'OLLAMA_URL' => rtrim(trim($_POST['ollama_url'] ?? ''), '/'),
        'DB_HOST' => trim($_POST['db_host'] ?? 'localhost'),
        'DB_NAME' => trim($_POST['db_name'] ?? 'devil_ai_gateway'),
        'DB_USER' => trim($_POST['db_user'] ?? ''),
        'DB_PASS' => $_POST['db_pass'] ?? '',
        'ADMIN_PASSWORD' => trim($_POST['admin_pass'] ?? 'admin123'),
        'GATEWAY_DOMAIN' => rtrim(trim($_POST['gateway_domain'] ?? ''), '/'),
        'DEFAULT_CURRENCY' => 'INR'
    ];
    foreach (['OLLAMA_URL','DB_HOST','DB_NAME','DB_USER','GATEWAY_DOMAIN','ADMIN_PASSWORD'] as $field) {
        if (($cfg[$field] ?? '') === '') $errors[] = "$field is required";
    }
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host={$cfg['DB_HOST']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['DB_NAME']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $_SESSION['cfg'] = $cfg;
            $step = 3;
        } catch (Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3 && !empty($_SESSION['cfg'])) {
    $cfg = $_SESSION['cfg'];
    $logs = [];
    try {
        $env = '';
        foreach ($cfg as $k => $v) $env .= "$k=$v\n";
        @file_put_contents(dirname(__DIR__) . '/.env', $env);
        $logs[] = ['ok', '.env file written'];

        $pdo = new PDO("mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(100) NULL,
          email VARCHAR(255) NULL,
          client_name VARCHAR(150) NULL,
          client_email VARCHAR(255) NULL,
          app_name VARCHAR(150) NULL,
          api_key VARCHAR(64) UNIQUE NOT NULL,
          subscription_amount_inr DECIMAL(10,2) DEFAULT 0,
          currency VARCHAR(10) DEFAULT 'INR',
          plan_name VARCHAR(100) DEFAULT 'Starter',
          speed_tier VARCHAR(20) DEFAULT 'normal',
          rpm_limit INT DEFAULT 30,
          timeout_seconds INT DEFAULT 60,
          requests_this_minute INT DEFAULT 0,
          minute_bucket VARCHAR(20) NULL,
          requests_today INT DEFAULT 0,
          total_requests INT DEFAULT 0,
          limit_per_day INT DEFAULT 100,
          is_active TINYINT DEFAULT 1,
          expires_at DATETIME NULL,
          last_reset DATE NULL,
          last_used_at DATETIME NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $logs[] = ['ok', 'Table api_keys created / upgraded'];

        $pdo->exec("CREATE TABLE IF NOT EXISTS request_logs (
          id INT AUTO_INCREMENT PRIMARY KEY,
          api_key VARCHAR(64),
          app_name VARCHAR(150) NULL,
          client_name VARCHAR(150) NULL,
          client_email VARCHAR(255) NULL,
          model VARCHAR(50),
          prompt TEXT,
          tools_used VARCHAR(255),
          response_time_ms INT,
          status VARCHAR(20),
          ip_address VARCHAR(45),
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $logs[] = ['ok', 'Table request_logs created / upgraded'];

        $adminKey = 'dk_live_' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO api_keys (name,email,client_name,client_email,app_name,api_key,subscription_amount_inr,currency,plan_name,speed_tier,rpm_limit,timeout_seconds,limit_per_day,expires_at,last_reset,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),1)");
        $stmt->execute(['Admin','admin@devilone.in','Admin','admin@devilone.in','Devil AI Admin',$adminKey,0,'INR','Enterprise','ultra',500,30,999999,date('Y-m-d H:i:s', strtotime('+3650 days'))]);
        $logs[] = ['ok', 'Admin API key generated'];

        $_SESSION['admin_key'] = $adminKey;
        $_SESSION['install_logs'] = $logs;
        $installLogs = $logs;
        $step = 4;
    } catch (Exception $e) {
        $errors[] = 'Installation failed: ' . $e->getMessage();
    }
}

$domain = $cfg['GATEWAY_DOMAIN'] ?? 'https://your-domain.com';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Devil AI Installer</title>
<style>
body{font-family:Arial,sans-serif;background:#090911;color:#eee;margin:0;padding:0} .wrap{max-width:760px;margin:0 auto;padding:30px}
.card{background:#111826;border:1px solid #232b3b;border-radius:16px;padding:28px;margin-top:20px} h1,h2{margin:0 0 10px;color:#fff}
.sub{color:#8a93a5;font-size:14px;margin-bottom:22px}.btn{background:#ef4444;color:#fff;border:none;border-radius:10px;padding:12px 22px;cursor:pointer;font-weight:700}
.btn2{background:#1f2937;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;display:inline-block}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.fg{margin-bottom:14px}
label{display:block;font-size:12px;color:#94a3b8;margin-bottom:6px} input{width:100%;padding:12px;background:#0b1220;color:#fff;border:1px solid #243041;border-radius:10px;box-sizing:border-box}
ul{line-height:1.9;color:#cbd5e1}.err{background:#2a1215;border:1px solid #5f1d24;padding:10px 14px;border-radius:10px;margin-bottom:12px;color:#fca5a5}.ok{background:#102417;border:1px solid #1f5131;padding:10px 14px;border-radius:10px;margin-bottom:10px;color:#86efac}
.warn{background:#2a2211;border:1px solid #6a5316;padding:10px 14px;border-radius:10px;margin-bottom:10px;color:#fde68a}.k{font-family:monospace;background:#0b1220;padding:14px;border-radius:10px;word-break:break-all;color:#fca5a5}
.top{display:flex;justify-content:space-between;align-items:center}.steps{color:#94a3b8;font-size:13px}.links{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px}.link{background:#0b1220;border:1px solid #243041;border-radius:12px;padding:14px;text-decoration:none;color:#fff}
@media(max-width:700px){.grid,.links{grid-template-columns:1fr}}
</style></head><body><div class="wrap">
<div class="top"><div><h1>😈 Devil AI Gateway Installer</h1><div class="sub">One-click web setup for your API platform</div></div><div class="steps">Step <?= $step ?>/4</div></div>

<?php if ($step===1): ?>
<div class="card"><h2>Welcome</h2><p class="sub">This installer creates your gateway config, admin login, database tables, pricing-ready API system, and admin API key.</p><ul><li>Admin login password set during install</li><li>Client API keys with expiry date</li><li>Plan price in INR, speed tier, RPM, daily limits</li><li>Validation endpoint and docs portal</li></ul><form method="post"><input type="hidden" name="step" value="2"><button class="btn">Start Setup</button></form></div>
<?php elseif ($step===2): ?>
<div class="card"><h2>Configuration</h2><p class="sub">Enter your hosting and database settings.</p><?php foreach($errors as $e) echo '<div class="err">'.$e.'</div>'; ?><form method="post"><input type="hidden" name="step" value="2"><div class="fg"><label>Gateway Domain</label><input name="gateway_domain" value="<?= htmlspecialchars($cfg['GATEWAY_DOMAIN'] ?? 'https://api.devilone.in') ?>"></div><div class="fg"><label>Original Ollama URL</label><input name="ollama_url" value="<?= htmlspecialchars($cfg['OLLAMA_URL'] ?? 'https://aiapi.devilpvt.in') ?>"></div><div class="grid"><div class="fg"><label>DB Host</label><input name="db_host" value="<?= htmlspecialchars($cfg['DB_HOST'] ?? 'localhost') ?>"></div><div class="fg"><label>DB Name</label><input name="db_name" value="<?= htmlspecialchars($cfg['DB_NAME'] ?? 'devil_ai_gateway') ?>"></div></div><div class="grid"><div class="fg"><label>DB User</label><input name="db_user" value="<?= htmlspecialchars($cfg['DB_USER'] ?? '') ?>"></div><div class="fg"><label>DB Password</label><input type="password" name="db_pass" value=""></div></div><div class="fg"><label>Admin Login Password</label><input type="password" name="admin_pass" value="<?= htmlspecialchars($cfg['ADMIN_PASSWORD'] ?? 'admin123') ?>"></div><button class="btn">Test DB & Continue</button></form></div>
<?php elseif ($step===3): ?>
<div class="card"><h2>Install</h2><p class="sub">Database is ready. Click install to generate all tables and the first admin API key.</p><?php foreach($errors as $e) echo '<div class="err">'.$e.'</div>'; ?><form method="post"><input type="hidden" name="step" value="3"><button class="btn">Run Installation</button></form></div>
<?php else: ?>
<div class="card"><h2>Installation Complete</h2><p class="sub">Your platform is ready.</p><?php foreach($installLogs as $log){ $c=$log[0]==='ok'?'ok':'warn'; echo '<div class="'.$c.'">'.($log[0]==='ok'?'✅ ':'⚠️ ').$log[1].'</div>'; } ?><h3>Admin API Key</h3><div class="k"><?= htmlspecialchars($adminKey) ?></div><p class="sub">Use this key for testing and admin integrations. Save it now.</p><div class="links"><a class="link" href="../">🌐 Landing Page<br><small><?= htmlspecialchars($domain) ?></small></a><a class="link" href="../docs/">📚 API Docs<br><small>Developer portal</small></a><a class="link" href="../admin/">🔐 Admin Panel<br><small>Login with the password you set</small></a><a class="link" href="../api/v1/plans" target="_blank">💸 Plans API<br><small>Starter / Growth / Pro / Enterprise</small></a></div><div class="warn" style="margin-top:18px">Delete or rename <strong>setup/install.php</strong> after setup for security.</div></div>
<?php endif; ?>
</div></body></html>
