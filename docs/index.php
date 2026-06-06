<?php
require_once __DIR__ . '/../api/config.php';
$domain = GATEWAY_DOMAIN;
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Devil AI API — Docs</title>
<style>
body{font-family:Arial,sans-serif;background:#0b1020;color:#e5e7eb;margin:0}.side{position:fixed;left:0;top:0;width:250px;height:100vh;background:#111827;border-right:1px solid #1f2937;padding:20px;overflow:auto}.side a{display:block;color:#9ca3af;text-decoration:none;padding:8px 0}.side a:hover{color:#fff}.main{margin-left:250px;padding:34px;max-width:980px}.card{background:#111827;border:1px solid #1f2937;border-radius:14px;padding:22px;margin:18px 0}.badge{display:inline-block;background:#ef4444;color:#fff;padding:4px 10px;border-radius:6px;font-size:12px}.get{background:#10b981}.post{background:#ef4444} pre{background:#020617;border:1px solid #1e293b;border-radius:10px;padding:14px;overflow:auto;color:#cbd5e1} table{width:100%;border-collapse:collapse} th,td{border-bottom:1px solid #1f2937;padding:10px;text-align:left} .muted{color:#94a3b8}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.plan{background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:16px} .k{font-family:monospace;color:#fca5a5}
@media(max-width:900px){.side{display:none}.main{margin-left:0}.grid{grid-template-columns:1fr}}
</style></head><body>
<div class="side"><h2>😈 Devil AI Docs</h2><a href="#overview">Overview</a><a href="#plans">Plans</a><a href="#create">Create API Key</a><a href="#validate">Validate API Key</a><a href="#chat">Chat API</a><a href="#models">Models</a><a href="#pricing">Pricing Logic</a></div>
<div class="main">
<section id="overview"><h1>Devil AI API</h1><p class="muted">Base URL: <span class="k"><?= $domain ?>/api/v1</span></p><div class="card">This gateway supports client app metadata, subscription pricing in INR, expiry dates, speed tiers, rate limits, validation endpoint, and admin-managed plans.</div></section>
<section id="plans"><h2>Plans</h2><div class="grid"><div class="plan"><h3>Starter</h3><p>₹499 / 30 days</p><p class="muted">Slow speed · 10 RPM · 200/day</p></div><div class="plan"><h3>Growth</h3><p>₹1499 / 30 days</p><p class="muted">Normal speed · 30 RPM · 1000/day</p></div><div class="plan"><h3>Pro</h3><p>₹3499 / 30 days</p><p class="muted">Fast speed · 60 RPM · 5000/day</p></div><div class="plan"><h3>Enterprise</h3><p>₹9999 / 30 days</p><p class="muted">Ultra speed · 120 RPM · 20000/day</p></div></div><pre>GET <?= $domain ?>/api/v1/plans</pre></section>
<section id="create"><h2>Create API Key</h2><div class="card"><span class="badge post">POST</span> <span class="k"><?= $domain ?>/api/v1/keys/create</span><p class="muted" style="margin-top:12px">Required: client_name, client_email, app_name</p><pre>{
  "client_name": "Acme Pvt Ltd",
  "client_email": "owner@acme.com",
  "app_name": "Acme Chat App",
  "plan_name": "Pro",
  "subscription_amount_inr": 3499,
  "speed_tier": "fast",
  "rpm_limit": 60,
  "limit_per_day": 5000,
  "valid_days": 30
}</pre></div></section>
<section id="validate"><h2>Validate API Key</h2><div class="card"><span class="badge post">POST</span> <span class="k"><?= $domain ?>/api/v1/keys/validate</span><pre>{
  "api_key": "dk_live_xxxxxxxxx"
}</pre><pre>{
  "valid": true,
  "client": {
    "client_name": "Acme Pvt Ltd",
    "client_email": "owner@acme.com",
    "app_name": "Acme Chat App"
  },
  "subscription": {
    "plan_name": "Pro",
    "subscription_amount_inr": 3499,
    "speed_tier": "fast",
    "rpm_limit": 60,
    "limit_per_day": 5000,
    "expires_at": "2026-07-06 12:00:00"
  }
}</pre></div></section>
<section id="chat"><h2>Chat API</h2><div class="card"><span class="badge post">POST</span> <span class="k"><?= $domain ?>/api/v1/chat</span><pre>Headers:
Content-Type: application/json
X-API-Key: dk_live_xxxxxxxxx</pre><pre>{
  "model": "devil-ai",
  "prompt": "latest AI news today"
}</pre><pre>{
  "response": "...",
  "client": {
    "app_name": "Acme Chat App",
    "plan_name": "Pro",
    "speed_tier": "fast"
  },
  "usage": {
    "requests_today": 11,
    "limit_per_day": 5000,
    "requests_this_minute": 3,
    "rpm_limit": 60,
    "response_ms": 812,
    "expires_at": "2026-07-06 12:00:00"
  }
}</pre></div></section>
<section id="models"><h2>Models</h2><div class="card"><span class="badge get">GET</span> <span class="k"><?= $domain ?>/api/v1/models</span></div></section>
<section id="pricing"><h2>Pricing Logic</h2><table><tr><th>Field</th><th>Meaning</th></tr><tr><td>subscription_amount_inr</td><td>Amount paid by client in INR</td></tr><tr><td>speed_tier</td><td>slow / normal / fast / ultra</td></tr><tr><td>rpm_limit</td><td>Requests per minute allowed</td></tr><tr><td>limit_per_day</td><td>Daily request quota</td></tr><tr><td>expires_at</td><td>API validity end date/time</td></tr><tr><td>app_name</td><td>Client application name bound to the key</td></tr></table></section>
</div></body></html>
