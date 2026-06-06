<?php
require_once __DIR__ . '/../api/config.php';
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$db = getDB();
$msg = '';

// Create key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $limit = intval($_POST['limit'] ?? 100);
    if ($name && $email) {
        $key = 'dk_live_' . bin2hex(random_bytes(16));
        $db->prepare("INSERT IGNORE INTO api_keys (name,email,api_key,limit_per_day,last_reset) VALUES(?,?,?,?,CURDATE())")
           ->execute([$name, $email, $key, $limit]);
        $msg = "✅ Key created: <code>$key</code>";
    }
}

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE api_keys SET is_active = 1 - is_active WHERE id=?")->execute([$_GET['toggle']]);
    header('Location: keys.php'); exit;
}

// Delete key
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM api_keys WHERE id=?")->execute([$_GET['delete']]);
    header('Location: keys.php'); exit;
}

$keys = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><title>API Keys — Devil AI</title>
<style>
body{font-family:sans-serif;background:#0a0a0a;color:#eee;padding:30px;}
h1{color:#ff4444;} a{color:#ff4444;}
form{background:#111;padding:20px;border-radius:8px;border:1px solid #333;max-width:500px;margin-bottom:30px;}
input{width:100%;padding:8px;background:#222;border:1px solid #444;color:#fff;border-radius:4px;margin:5px 0 10px;box-sizing:border-box;}
.btn{background:#ff4444;color:#fff;border:none;padding:10px 20px;cursor:pointer;border-radius:5px;}
table{width:100%;border-collapse:collapse;} th{background:#ff4444;color:#fff;padding:8px;text-align:left;font-size:13px;}
td{padding:8px;border-bottom:1px solid #222;font-size:12px;} .badge{padding:2px 7px;border-radius:3px;font-size:11px;}
.active{background:#1a4a2a;color:#00ff88;} .inactive{background:#4a1a1a;color:#ff4444;}
</style></head><body>
<p><a href="index.php">← Dashboard</a></p>
<h1>🔑 API Key Management</h1>
<?php if ($msg) echo "<p style='color:#00ff88'>$msg</p>"; ?>
<form method="POST">
<label>Name</label><input name="name" required placeholder="Client name">
<label>Email</label><input name="email" type="email" required placeholder="client@example.com">
<label>Daily Limit</label><input name="limit" type="number" value="100">
<button class="btn" name="create">Generate API Key</button>
</form>
<table>
<tr><th>Name</th><th>Email</th><th>API Key</th><th>Daily Limit</th><th>Today</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($keys as $k): ?>
<tr>
<td><?= htmlspecialchars($k['name']) ?></td>
<td><?= htmlspecialchars($k['email']) ?></td>
<td style="font-family:monospace;font-size:11px"><?= htmlspecialchars($k['api_key']) ?></td>
<td><?= $k['limit_per_day'] ?></td>
<td><?= $k['requests_today'] ?></td>
<td><span class="badge <?= $k['is_active'] ? 'active' : 'inactive' ?>"><?= $k['is_active'] ? 'Active' : 'Inactive' ?></span></td>
<td>
    <a href="?toggle=<?= $k['id'] ?>"><?= $k['is_active'] ? 'Disable' : 'Enable' ?></a> |
    <a href="?delete=<?= $k['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</body></html>
