<?php
require_once __DIR__ . '/../api/config.php';
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
$db = getDB();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $clientName = trim($_POST['client_name'] ?? '');
    $clientEmail = trim($_POST['client_email'] ?? '');
    $appName = trim($_POST['app_name'] ?? '');
    $planName = trim($_POST['plan_name'] ?? 'Starter');
    $amount = floatval($_POST['subscription_amount_inr'] ?? 0);
    $speedTier = strtolower(trim($_POST['speed_tier'] ?? 'normal'));
    $limitPerDay = intval($_POST['limit_per_day'] ?? 100);
    $validDays = intval($_POST['valid_days'] ?? 30);
    $rpmLimit = intval($_POST['rpm_limit'] ?? 0);
    $tier = speedTierConfig($speedTier);
    if ($rpmLimit <= 0) $rpmLimit = $tier['rpm'];
    $timeout = $tier['timeout'];
    $key = generateApiKey();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(1, $validDays) . ' days'));
    $db->prepare("INSERT INTO api_keys (name,email,client_name,client_email,app_name,api_key,subscription_amount_inr,currency,plan_name,speed_tier,rpm_limit,timeout_seconds,limit_per_day,expires_at,last_reset,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),1)")
       ->execute([$clientName,$clientEmail,$clientName,$clientEmail,$appName,$key,$amount,'INR',$planName,$speedTier,$rpmLimit,$timeout,$limitPerDay,$expiresAt]);
    $msg = "✅ API key created: <code>$key</code>";
}
if (isset($_GET['toggle'])) { $db->prepare("UPDATE api_keys SET is_active = 1 - is_active WHERE id = ?")->execute([$_GET['toggle']]); header('Location: keys.php'); exit; }
$rows = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><title>Manage Keys</title><style>body{font-family:Arial;background:#0b1020;color:#e5e7eb;padding:24px}a{color:#f87171}.card{background:#111827;border:1px solid #1f2937;border-radius:14px;padding:20px;margin-bottom:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}label{display:block;font-size:12px;color:#94a3b8;margin-bottom:6px}input,select{width:100%;padding:11px;background:#0f172a;border:1px solid #334155;color:#fff;border-radius:8px;box-sizing:border-box}.btn{background:#ef4444;color:#fff;border:none;padding:12px 18px;border-radius:8px;cursor:pointer;font-weight:700}table{width:100%;border-collapse:collapse;background:#111827;border:1px solid #1f2937}th,td{padding:10px;border-bottom:1px solid #1f2937;font-size:12px;text-align:left}@media(max-width:800px){.grid{grid-template-columns:1fr}}</style></head><body><p><a href="index.php">← Dashboard</a></p><div class="card"><h2>Create Client API Key</h2><?php if($msg) echo '<p>'.$msg.'</p>'; ?><form method="post"><div class="grid"><div><label>Client Name</label><input name="client_name" required></div><div><label>Client Email</label><input type="email" name="client_email" required></div><div><label>App Name</label><input name="app_name" required></div><div><label>Plan Name</label><input name="plan_name" value="Starter"></div><div><label>Subscription Amount (INR)</label><input type="number" step="0.01" name="subscription_amount_inr" value="499"></div><div><label>Speed Tier</label><select name="speed_tier"><option>slow</option><option selected>normal</option><option>fast</option><option>ultra</option></select></div><div><label>RPM Limit</label><input type="number" name="rpm_limit" value="30"></div><div><label>Daily Limit</label><input type="number" name="limit_per_day" value="1000"></div><div><label>Valid Days</label><input type="number" name="valid_days" value="30"></div></div><p style="margin-top:14px"><button class="btn" name="create">Generate API Key</button></p></form></div><table><tr><th>App</th><th>Client</th><th>Email</th><th>Plan</th><th>Amount</th><th>Speed</th><th>RPM</th><th>Daily</th><th>Expiry</th><th>API Key</th><th>Action</th></tr><?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['app_name']) ?></td><td><?= htmlspecialchars($r['client_name']) ?></td><td><?= htmlspecialchars($r['client_email']) ?></td><td><?= htmlspecialchars($r['plan_name']) ?></td><td>₹<?= number_format((float)$r['subscription_amount_inr'],2) ?></td><td><?= htmlspecialchars($r['speed_tier']) ?></td><td><?= (int)$r['rpm_limit'] ?></td><td><?= (int)$r['limit_per_day'] ?></td><td><?= htmlspecialchars((string)$r['expires_at']) ?></td><td style="font-family:monospace"><?= htmlspecialchars($r['api_key']) ?></td><td><a href="?toggle=<?= $r['id'] ?>"><?= (int)$r['is_active']===1?'Disable':'Enable' ?></a></td></tr><?php endforeach; ?></table></body></html>
