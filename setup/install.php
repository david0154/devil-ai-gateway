<?php
// ============================================
// Devil AI Gateway — Full Web Installer Wizard
// Visit: your-domain.com/setup/install.php
// ============================================
session_start();

$step    = intval($_POST['step'] ?? $_GET['step'] ?? 1);
$errors  = [];
$success = false;

// ---- STEP 2: Save config and test DB ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $cfg = [
        'OLLAMA_URL'      => rtrim(trim($_POST['ollama_url'] ?? ''), '/'),
        'DB_HOST'         => trim($_POST['db_host'] ?? 'localhost'),
        'DB_NAME'         => trim($_POST['db_name'] ?? 'devil_ai_gateway'),
        'DB_USER'         => trim($_POST['db_user'] ?? ''),
        'DB_PASS'         => $_POST['db_pass'] ?? '',
        'ADMIN_PASSWORD'  => trim($_POST['admin_pass'] ?? 'admin123'),
        'GATEWAY_DOMAIN'  => rtrim(trim($_POST['gateway_domain'] ?? ''), '/'),
    ];
    foreach (['OLLAMA_URL','DB_HOST','DB_NAME','DB_USER','GATEWAY_DOMAIN'] as $k) {
        if (empty($cfg[$k])) $errors[] = "$k is required";
    }
    if (empty($errors)) {
        // Test DB connection
        try {
            $pdo = new PDO("mysql:host={$cfg['DB_HOST']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['DB_NAME']}`");
            $_SESSION['cfg'] = $cfg;
            $step = 3;
        } catch (Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

// ---- STEP 3: Write .env + Create tables ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3 && !empty($_SESSION['cfg'])) {
    $cfg = $_SESSION['cfg'];
    $envContent = implode("\n", array_map(fn($k,$v) => "$k=$v", array_keys($cfg), $cfg)) . "\n";
    $envPath = dirname(__DIR__) . '/.env';
    $wrote = @file_put_contents($envPath, $envContent);

    $logs = [];
    try {
        $pdo = new PDO("mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

        $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100), email VARCHAR(255),
            api_key VARCHAR(64) UNIQUE NOT NULL,
            requests_today INT DEFAULT 0,
            total_requests INT DEFAULT 0,
            limit_per_day INT DEFAULT 100,
            is_active TINYINT DEFAULT 1,
            last_reset DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $logs[] = ['ok', 'Table <code>api_keys</code> created'];

        $pdo->exec("CREATE TABLE IF NOT EXISTS request_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            api_key VARCHAR(64), model VARCHAR(50),
            prompt TEXT, tools_used VARCHAR(255),
            response_time_ms INT, status VARCHAR(20),
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $logs[] = ['ok', 'Table <code>request_logs</code> created'];

        $adminKey = 'dk_live_' . bin2hex(random_bytes(16));
        $pdo->prepare("INSERT IGNORE INTO api_keys (name,email,api_key,limit_per_day) VALUES(?,?,?,?)")
            ->execute(['Admin', 'admin@devilone.in', $adminKey, 99999]);
        $logs[] = ['ok', 'Admin API key created'];
        $_SESSION['admin_key'] = $adminKey;
        $_SESSION['cfg'] = $cfg;

        if ($wrote !== false) {
            $logs[] = ['ok', '.env file written successfully'];
        } else {
            $logs[] = ['warn', '.env write failed — copy config manually (shown in step 4)'];
        }

        $step    = 4;
        $success = true;

    } catch (Exception $e) {
        $errors[] = 'Setup failed: ' . $e->getMessage();
        $step = 3;
    }
}

$cfg      = $_SESSION['cfg'] ?? [];
$adminKey = $_SESSION['admin_key'] ?? '';
$domain   = $cfg['GATEWAY_DOMAIN'] ?? 'https://your-domain.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Devil AI Gateway — Installer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#080810;color:#e0e0e0;min-height:100vh;}

/* Animated background */
body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:radial-gradient(ellipse at 20% 50%,rgba(255,68,68,.08) 0,transparent 50%),radial-gradient(ellipse at 80% 20%,rgba(120,40,200,.08) 0,transparent 50%);pointer-events:none;z-index:0;}

.wrapper{position:relative;z-index:1;max-width:680px;margin:0 auto;padding:40px 20px;}

/* Logo */
.logo{text-align:center;margin-bottom:40px;}
.logo-icon{font-size:56px;display:block;margin-bottom:10px;filter:drop-shadow(0 0 20px rgba(255,68,68,.5));}
.logo h1{font-size:28px;font-weight:700;background:linear-gradient(135deg,#ff4444,#ff8800);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.logo p{color:#666;font-size:14px;margin-top:6px;}

/* Stepper */
.stepper{display:flex;justify-content:center;gap:0;margin-bottom:40px;}
.step-item{display:flex;align-items:center;}
.step-circle{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;border:2px solid #333;color:#555;transition:all .3s;}
.step-circle.active{border-color:#ff4444;color:#ff4444;box-shadow:0 0 15px rgba(255,68,68,.3);}
.step-circle.done{background:#ff4444;border-color:#ff4444;color:#fff;}
.step-label{font-size:11px;color:#555;margin-top:4px;text-align:center;}
.step-wrap{display:flex;flex-direction:column;align-items:center;}
.step-line{width:60px;height:2px;background:#222;margin-bottom:18px;}
.step-line.done{background:#ff4444;}

/* Card */
.card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:36px;backdrop-filter:blur(10px);}
.card-title{font-size:20px;font-weight:600;color:#fff;margin-bottom:6px;}
.card-sub{color:#666;font-size:13px;margin-bottom:28px;}

/* Form */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{margin-bottom:20px;}
label{display:block;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px;}
input[type=text],input[type=url],input[type=password],input[type=email]{
    width:100%;padding:12px 16px;background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.1);border-radius:8px;color:#fff;
    font-size:14px;transition:border .2s;
}
input:focus{outline:none;border-color:#ff4444;background:rgba(255,68,68,.05);}
input::placeholder{color:#444;}

.hint{font-size:11px;color:#555;margin-top:5px;}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,#ff4444,#cc2222);color:#fff;box-shadow:0 4px 20px rgba(255,68,68,.3);}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(255,68,68,.4);}
.btn-ghost{background:transparent;color:#888;border:1px solid #333;}
.btn-ghost:hover{border-color:#555;color:#ccc;}
.btn-full{width:100%;justify-content:center;}
.btn-group{display:flex;gap:12px;margin-top:24px;}

/* Status items */
.status-list{list-style:none;padding:0;}
.status-list li{padding:10px 14px;border-radius:8px;font-size:14px;margin-bottom:8px;display:flex;align-items:center;gap:10px;}
.status-ok{background:rgba(0,200,100,.08);border:1px solid rgba(0,200,100,.15);color:#00cc66;}
.status-warn{background:rgba(255,180,0,.08);border:1px solid rgba(255,180,0,.15);color:#ffb400;}
.status-error{background:rgba(255,60,60,.08);border:1px solid rgba(255,60,60,.15);color:#ff4444;}

/* Error box */
.errors{background:rgba(255,60,60,.08);border:1px solid rgba(255,60,60,.2);border-radius:8px;padding:14px 18px;margin-bottom:20px;}
.errors p{color:#ff6666;font-size:13px;margin:3px 0;}

/* Key display */
.key-box{background:#0a0a15;border:1px solid rgba(255,68,68,.3);border-radius:10px;padding:18px;margin:16px 0;}
.key-box code{font-family:'Courier New',monospace;font-size:14px;color:#ff6666;word-break:break-all;display:block;margin-bottom:10px;}
.key-box .copy-btn{background:rgba(255,68,68,.15);border:1px solid rgba(255,68,68,.3);color:#ff6666;padding:6px 14px;border-radius:5px;font-size:12px;cursor:pointer;transition:all .2s;}
.key-box .copy-btn:hover{background:rgba(255,68,68,.3);}

/* Quick links */
.links-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:24px;}
.link-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:18px;text-decoration:none;color:#ccc;transition:all .2s;display:flex;align-items:center;gap:12px;}
.link-card:hover{border-color:rgba(255,68,68,.4);background:rgba(255,68,68,.05);color:#fff;transform:translateY(-2px);}
.link-card .icon{font-size:24px;}
.link-card .lbl{font-size:13px;font-weight:600;color:#fff;}
.link-card .sub{font-size:11px;color:#666;margin-top:2px;}

/* Config preview */
.config-box{background:#070710;border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:16px;font-family:monospace;font-size:12px;color:#888;white-space:pre-wrap;overflow-x:auto;margin:12px 0;max-height:200px;overflow-y:auto;}

/* Progress bar */
.progress{height:3px;background:#222;border-radius:2px;overflow:hidden;margin-bottom:30px;}
.progress-bar{height:100%;background:linear-gradient(90deg,#ff4444,#ff8800);border-radius:2px;transition:width .5s;}

@media(max-width:500px){.form-row{grid-template-columns:1fr;}.links-grid{grid-template-columns:1fr;}.step-line{width:30px;}}
</style>
</head>
<body>
<div class="wrapper">

    <!-- Logo -->
    <div class="logo">
        <span class="logo-icon">😈</span>
        <h1>Devil AI Gateway</h1>
        <p>Web Installation Wizard &nbsp;·&nbsp; Devil One Pvt Ltd</p>
    </div>

    <!-- Stepper -->
    <div class="stepper">
        <?php
        $steps = ['Welcome','Database','Install','Done'];
        foreach ($steps as $i => $label):
            $n = $i + 1;
            $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
        ?>
        <div class="step-wrap">
            <div class="step-circle <?= $cls ?>"><?= $n < $step ? '✓' : $n ?></div>
            <div class="step-label"><?= $label ?></div>
        </div>
        <?php if ($n < count($steps)): ?>
            <div class="step-line <?= $n < $step ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Progress -->
    <div class="progress"><div class="progress-bar" style="width:<?= ($step/4)*100 ?>%"></div></div>

    <!-- STEP 1: Welcome -->
    <?php if ($step === 1): ?>
    <div class="card">
        <div class="card-title">👋 Welcome to the Installer</div>
        <div class="card-sub">This wizard will configure your Devil AI Gateway in under 2 minutes.</div>

        <p style="color:#aaa;font-size:14px;line-height:1.8;margin-bottom:20px">
            The installer will:
        </p>
        <ul style="color:#888;font-size:13px;line-height:2.2;padding-left:20px;margin-bottom:24px;">
            <li>✅ Connect and configure your MySQL database</li>
            <li>✅ Write your <code style="color:#ff6666">.env</code> configuration file</li>
            <li>✅ Create all required tables (<code style="color:#ff6666">api_keys</code>, <code style="color:#ff6666">request_logs</code>)</li>
            <li>✅ Generate your first admin API key</li>
            <li>✅ Launch your API gateway + documentation portal</li>
        </ul>

        <div style="background:rgba(255,180,0,.06);border:1px solid rgba(255,180,0,.15);border-radius:8px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#ffb400;">
            ⚠️ <strong>Requirements:</strong> PHP 8.0+, MySQL 5.7+, cURL enabled, allow_url_fopen = On
        </div>

        <form method="POST">
            <input type="hidden" name="step" value="2">
            <button class="btn btn-primary btn-full" type="submit">Get Started →</button>
        </form>
    </div>

    <!-- STEP 2: Configure -->
    <?php elseif ($step === 2): ?>
    <div class="card">
        <div class="card-title">⚙️ Configuration</div>
        <div class="card-sub">Enter your database credentials and gateway settings.</div>

        <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>❌ $e</p>"; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="step" value="2">

            <div class="form-group">
                <label>Ollama / AI Backend URL</label>
                <input type="url" name="ollama_url" value="https://aiapi.devilpvt.in" required placeholder="https://aiapi.devilpvt.in">
                <div class="hint">Your private Ollama instance URL</div>
            </div>

            <div class="form-group">
                <label>Your Gateway Domain</label>
                <input type="url" name="gateway_domain" value="https://api.devilone.in" required placeholder="https://api.devilone.in">
                <div class="hint">The public URL where this gateway is hosted</div>
            </div>

            <hr style="border:none;border-top:1px solid #1a1a1a;margin:20px 0;">
            <p style="font-size:12px;color:#555;margin-bottom:20px;text-transform:uppercase;letter-spacing:.5px;">Database Settings</p>

            <div class="form-row">
                <div class="form-group">
                    <label>DB Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>DB Name</label>
                    <input type="text" name="db_name" value="devil_ai_gateway" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>DB Username</label>
                    <input type="text" name="db_user" required placeholder="root">
                </div>
                <div class="form-group">
                    <label>DB Password</label>
                    <input type="password" name="db_pass" placeholder="(leave blank if none)">
                </div>
            </div>

            <hr style="border:none;border-top:1px solid #1a1a1a;margin:20px 0;">

            <div class="form-group">
                <label>Admin Panel Password</label>
                <input type="password" name="admin_pass" value="admin123" required>
                <div class="hint">Used to login to /admin/ panel</div>
            </div>

            <div class="btn-group">
                <a href="?step=1" class="btn btn-ghost">← Back</a>
                <button class="btn btn-primary" style="flex:1;justify-content:center;" type="submit">Test Connection →</button>
            </div>
        </form>
    </div>

    <!-- STEP 3: Run Install -->
    <?php elseif ($step === 3): ?>
    <div class="card">
        <div class="card-title">🗄️ Ready to Install</div>
        <div class="card-sub">Database connected successfully. Click install to create tables and write config.</div>

        <div style="background:rgba(0,200,100,.06);border:1px solid rgba(0,200,100,.15);border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#00cc66;">
            ✅ Database connection successful: <strong><?= htmlspecialchars($cfg['DB_HOST'].'/'.$cfg['DB_NAME']) ?></strong>
        </div>

        <p style="font-size:12px;color:#555;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;">Config Preview</p>
        <div class="config-box"><?php
            foreach ($cfg as $k => $v) {
                $display = (stripos($k,'PASS') !== false || stripos($k,'PASSWORD') !== false) ? str_repeat('*', max(6, strlen($v))) : $v;
                echo htmlspecialchars("$k=$display") . "\n";
            }
        ?></div>

        <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>❌ $e</p>"; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="step" value="3">
            <div class="btn-group">
                <a href="?step=1" class="btn btn-ghost">Start Over</a>
                <button class="btn btn-primary" style="flex:1;justify-content:center;" type="submit">🚀 Run Installation</button>
            </div>
        </form>
    </div>

    <!-- STEP 4: Done -->
    <?php elseif ($step === 4): ?>
    <div class="card">
        <div class="card-title">🎉 Installation Complete!</div>
        <div class="card-sub">Devil AI Gateway is ready. Save your admin key below.</div>

        <ul class="status-list">
            <?php if (!empty($logs)) foreach ($logs as [$type,$msg]): ?>
            <li class="status-<?= $type === 'ok' ? 'ok' : ($type === 'warn' ? 'warn' : 'error') ?>">
                <?= $type === 'ok' ? '✅' : ($type === 'warn' ? '⚠️' : '❌') ?>
                <?= $msg ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <div style="margin:24px 0;">
            <p style="font-size:12px;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">🔑 Your Admin API Key</p>
            <div class="key-box">
                <code id="adminKey"><?= htmlspecialchars($adminKey) ?></code>
                <button class="copy-btn" onclick="copyKey()">📋 Copy Key</button>
            </div>
            <p style="font-size:12px;color:#ff6666;margin-top:8px;">⚠️ Save this now — it won't be shown again!</p>
        </div>

        <p style="font-size:12px;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">Quick Links</p>
        <div class="links-grid">
            <a href="../docs/" class="link-card">
                <span class="icon">📚</span>
                <div><div class="lbl">API Documentation</div><div class="sub">Developer portal & playground</div></div>
            </a>
            <a href="../admin/" class="link-card">
                <span class="icon">🔐</span>
                <div><div class="lbl">Admin Panel</div><div class="sub">Manage keys & view logs</div></div>
            </a>
            <a href="../api/v1/models" target="_blank" class="link-card">
                <span class="icon">🤖</span>
                <div><div class="lbl">Test API</div><div class="sub">List available models</div></div>
            </a>
            <a href="https://github.com/david0154/devil-ai-gateway" target="_blank" class="link-card">
                <span class="icon">⭐</span>
                <div><div class="lbl">GitHub Repo</div><div class="sub">Source code & updates</div></div>
            </a>
        </div>

        <div style="background:rgba(255,180,0,.06);border:1px solid rgba(255,180,0,.15);border-radius:8px;padding:14px 18px;margin-top:24px;font-size:13px;color:#ffb400;">
            🔒 <strong>Security:</strong> Delete or rename <code>setup/install.php</code> after installation!
        </div>
    </div>
    <?php endif; ?>

    <p style="text-align:center;color:#333;font-size:12px;margin-top:30px;">
        😈 Devil AI Gateway &nbsp;·&nbsp; <a href="https://devilone.in" style="color:#555;">devilone.in</a> &nbsp;·&nbsp; Built by David
    </p>
</div>

<script>
function copyKey() {
    const key = document.getElementById('adminKey')?.textContent;
    if (!key) return;
    navigator.clipboard.writeText(key).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '✅ Copied!';
        setTimeout(() => btn.textContent = '📋 Copy Key', 2000);
    });
}
</script>
</body>
</html>
