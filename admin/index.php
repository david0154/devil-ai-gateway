<?php
// ============================================
// Devil AI Gateway — Admin Dashboard
// ============================================

require_once __DIR__ . '/../api/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    } else {
        $error = 'Wrong password';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['admin'])): ?>
<!DOCTYPE html><html><head><title>Admin Login — Devil AI</title>
<style>body{font-family:sans-serif;background:#0a0a0a;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.box{background:#111;padding:40px;border-radius:10px;border:1px solid #ff4444;min-width:300px;}
input{width:100%;padding:10px;margin:10px 0;background:#222;border:1px solid #444;color:#fff;border-radius:5px;box-sizing:border-box;}
.btn{width:100%;background:#ff4444;color:#fff;border:none;padding:12px;font-size:16px;cursor:pointer;border-radius:5px;}
.err{color:#ff4444;}</style></head><body>
<div class="box">
<h2 style="color:#ff4444;text-align:center">😈 Devil AI Admin</h2>
<?php if (isset($error)) echo "<p class='err'>$error</p>"; ?>
<form method="POST">
<input type="password" name="password" placeholder="Admin Password" required>
<button class="btn">Login</button>
</form></div></body></html>
<?php exit; endif;

// Fetch stats
try {
    $db = getDB();
    $totalKeys    = $db->query("SELECT COUNT(*) FROM api_keys")->fetchColumn();
    $activeKeys   = $db->query("SELECT COUNT(*) FROM api_keys WHERE is_active=1")->fetchColumn();
    $totalReqs    = $db->query("SELECT SUM(total_requests) FROM api_keys")->fetchColumn();
    $todayReqs    = $db->query("SELECT SUM(requests_today) FROM api_keys")->fetchColumn();
    $recentLogs   = $db->query("SELECT * FROM request_logs ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $keys         = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $dbError = $e->getMessage(); }
?>
<!DOCTYPE html><html><head><title>Admin — Devil AI Gateway</title>
<style>
body{font-family:sans-serif;background:#0a0a0a;color:#eee;margin:0;padding:0;}
.nav{background:#ff4444;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;}
.nav h1{margin:0;color:#fff;font-size:20px;} .nav a{color:#fff;text-decoration:none;margin-left:15px;}
.container{padding:30px;}
.cards{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:30px;}
.card{background:#111;border:1px solid #333;border-radius:10px;padding:20px;min-width:150px;text-align:center;}
.card .num{font-size:36px;font-weight:bold;color:#ff4444;}
.card .lbl{color:#888;font-size:13px;}
table{width:100%;border-collapse:collapse;background:#111;border-radius:8px;overflow:hidden;}
th{background:#ff4444;color:#fff;padding:10px;text-align:left;font-size:13px;}
td{padding:10px;border-bottom:1px solid #222;font-size:13px;}
tr:hover td{background:#1a1a1a;}
.badge{padding:3px 8px;border-radius:4px;font-size:11px;}
.active{background:#1a4a2a;color:#00ff88;} .inactive{background:#4a1a1a;color:#ff4444;}
h2{color:#ff4444;margin-top:30px;}
.btn-sm{background:#ff4444;color:#fff;border:none;padding:5px 10px;cursor:pointer;border-radius:4px;font-size:12px;}
</style></head><body>
<div class="nav">
    <h1>😈 Devil AI Gateway — Admin</h1>
    <div>
        <a href="keys.php">🔑 Keys</a>
        <a href="logs.php">📋 Logs</a>
        <a href="../docs/">📚 Docs</a>
        <form method="POST" style="display:inline"><button name="logout" style="background:none;border:none;color:#fff;cursor:pointer;margin-left:15px;">Logout</button></form>
    </div>
</div>
<div class="container">
<?php if (isset($dbError)): ?>
    <p style="color:#ff4444">DB Error: <?= htmlspecialchars($dbError) ?></p>
<?php else: ?>
<div class="cards">
    <div class="card"><div class="num"><?= $totalKeys ?></div><div class="lbl">Total API Keys</div></div>
    <div class="card"><div class="num"><?= $activeKeys ?></div><div class="lbl">Active Keys</div></div>
    <div class="card"><div class="num"><?= number_format($totalReqs ?? 0) ?></div><div class="lbl">Total Requests</div></div>
    <div class="card"><div class="num"><?= number_format($todayReqs ?? 0) ?></div><div class="lbl">Today's Requests</div></div>
</div>

<h2>🔑 API Keys</h2>
<table>
<tr><th>Name</th><th>Email</th><th>API Key</th><th>Today</th><th>Limit/Day</th><th>Total</th><th>Status</th></tr>
<?php foreach ($keys as $k): ?>
<tr>
    <td><?= htmlspecialchars($k['name']) ?></td>
    <td><?= htmlspecialchars($k['email']) ?></td>
    <td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($k['api_key']) ?></td>
    <td><?= $k['requests_today'] ?></td>
    <td><?= $k['limit_per_day'] ?></td>
    <td><?= $k['total_requests'] ?></td>
    <td><span class="badge <?= $k['is_active'] ? 'active' : 'inactive' ?>"><?= $k['is_active'] ? 'Active' : 'Inactive' ?></span></td>
</tr>
<?php endforeach; ?>
</table>

<h2>📋 Recent Requests</h2>
<table>
<tr><th>Time</th><th>Key</th><th>Model</th><th>Tools</th><th>Prompt</th><th>Time(ms)</th><th>IP</th></tr>
<?php foreach ($recentLogs as $log): ?>
<tr>
    <td><?= $log['created_at'] ?></td>
    <td style="font-family:monospace;font-size:10px"><?= substr($log['api_key'],0,20).'...' ?></td>
    <td><?= htmlspecialchars($log['model']) ?></td>
    <td><?= htmlspecialchars($log['tools_used']) ?></td>
    <td><?= htmlspecialchars(substr($log['prompt'],0,50)) ?>...</td>
    <td><?= $log['response_time_ms'] ?></td>
    <td><?= $log['ip_address'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div></body></html>
