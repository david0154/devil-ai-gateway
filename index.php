<?php
// Devil AI Gateway — Public Landing Page
require_once __DIR__ . '/api/config.php';
$domain = GATEWAY_DOMAIN;

// Fetch live model count
$models = [];
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, OLLAMA_URL . '/api/tags');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $r = @json_decode(curl_exec($ch), true);
    curl_close($ch);
    $models = $r['models'] ?? [];
} catch(Exception $e) {}
$online = !empty($models);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Devil AI — API Platform</title>
<meta name="description" content="Devil AI is a fast, powerful AI API powered by Ollama. Get real-time AI responses with built-in tool calling for weather, news, GitHub, and more.">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#080810;color:#e0e0e0;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}

/* Navbar */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:16px 40px;display:flex;justify-content:space-between;align-items:center;background:rgba(8,8,16,.8);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.05);}
.nav-logo{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:700;}
.nav-logo span{font-size:24px;}
.nav-logo strong{background:linear-gradient(135deg,#ff4444,#ff8800);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.nav-links{display:flex;gap:24px;align-items:center;}
.nav-links a{color:#888;font-size:14px;font-weight:500;transition:color .2s;}
.nav-links a:hover{color:#fff;}
.nav-btn{background:linear-gradient(135deg,#ff4444,#cc2222);color:#fff!important;padding:9px 20px;border-radius:7px;font-size:14px;font-weight:600;transition:all .2s!important;}
.nav-btn:hover{transform:translateY(-1px);box-shadow:0 4px 20px rgba(255,68,68,.4);}

/* Hero */
.hero{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:120px 20px 80px;position:relative;}
.hero::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:radial-gradient(ellipse 80% 60% at 50% 40%,rgba(255,68,68,.12) 0,transparent 70%);pointer-events:none;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,68,68,.1);border:1px solid rgba(255,68,68,.2);border-radius:100px;padding:6px 16px;font-size:12px;color:#ff8888;margin-bottom:24px;}
.status-dot{width:8px;height:8px;border-radius:50%;background:<?= $online ? '#00cc66' : '#ff4444' ?>;box-shadow:0 0 10px <?= $online ? '#00cc66' : '#ff4444' ?>;animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.5;}}
.hero h1{font-size:clamp(40px,7vw,80px);font-weight:800;line-height:1.1;margin-bottom:20px;}
.hero h1 span{background:linear-gradient(135deg,#ff4444 0%,#ff8800 50%,#ffcc00 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:18px;color:#888;max-width:540px;margin:0 auto 40px;line-height:1.7;}
.hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:60px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-radius:9px;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;border:none;}
.btn-red{background:linear-gradient(135deg,#ff4444,#cc2222);color:#fff;box-shadow:0 4px 20px rgba(255,68,68,.3);}
.btn-red:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(255,68,68,.4);}
.btn-outline{background:transparent;color:#ccc;border:1px solid rgba(255,255,255,.15);}
.btn-outline:hover{border-color:rgba(255,68,68,.5);color:#fff;background:rgba(255,68,68,.05);}

/* Code preview */
.code-preview{background:rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:20px 24px;max-width:600px;margin:0 auto;text-align:left;font-family:monospace;font-size:13px;line-height:1.8;}
.code-preview .cm{color:#555;} .code-preview .kw{color:#ff6666;} .code-preview .str{color:#88cc44;} .code-preview .key{color:#66aaff;}

/* Stats bar */
.stats{display:flex;justify-content:center;gap:50px;padding:50px 20px;border-top:1px solid rgba(255,255,255,.05);border-bottom:1px solid rgba(255,255,255,.05);flex-wrap:wrap;}
.stat{text-align:center;}
.stat-num{font-size:36px;font-weight:800;background:linear-gradient(135deg,#ff4444,#ff8800);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat-lbl{font-size:13px;color:#555;margin-top:4px;}

/* Features */
.features{padding:100px 40px;max-width:1100px;margin:0 auto;}
.section-title{text-align:center;margin-bottom:60px;}
.section-title h2{font-size:36px;font-weight:700;color:#fff;margin-bottom:10px;}
.section-title p{color:#666;font-size:16px;}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;}
.feature-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:28px;transition:all .3s;}
.feature-card:hover{border-color:rgba(255,68,68,.3);background:rgba(255,68,68,.04);transform:translateY(-4px);}
.feature-icon{font-size:32px;margin-bottom:16px;}
.feature-card h3{font-size:17px;font-weight:600;color:#fff;margin-bottom:8px;}
.feature-card p{font-size:14px;color:#666;line-height:1.7;}

/* Tools section */
.tools-section{padding:80px 40px;background:rgba(255,255,255,.01);}
.tools-inner{max-width:900px;margin:0 auto;}
.tools-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-top:40px;}
.tool-badge{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:20px;text-align:center;transition:all .2s;}
.tool-badge:hover{border-color:rgba(255,68,68,.3);background:rgba(255,68,68,.05);}
.tool-badge .ti{font-size:28px;margin-bottom:8px;}
.tool-badge .tn{font-size:13px;font-weight:600;color:#ccc;}
.tool-badge .tk{font-size:11px;color:#555;margin-top:4px;}

/* CTA */
.cta{padding:100px 20px;text-align:center;}
.cta h2{font-size:42px;font-weight:800;color:#fff;margin-bottom:16px;}
.cta p{color:#666;font-size:16px;margin-bottom:40px;}

/* Endpoint showcase */
.endpoints{padding:80px 40px;max-width:900px;margin:0 auto;}
.ep-card{background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:24px;margin-bottom:16px;}
.ep-header{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.method{padding:4px 12px;border-radius:5px;font-size:12px;font-weight:700;font-family:monospace;}
.post{background:rgba(255,100,0,.2);color:#ff8844;border:1px solid rgba(255,100,0,.3);}
.get{background:rgba(0,200,100,.15);color:#00cc66;border:1px solid rgba(0,200,100,.25);}
.ep-url{font-family:monospace;color:#aaa;font-size:14px;}
.ep-desc{color:#666;font-size:13px;}

/* Footer */
footer{border-top:1px solid rgba(255,255,255,.05);padding:40px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;max-width:1100px;margin:0 auto;}
footer p{color:#444;font-size:13px;}
.footer-links{display:flex;gap:20px;}
.footer-links a{color:#444;font-size:13px;transition:color .2s;}
.footer-links a:hover{color:#ff4444;}

@media(max-width:600px){.nav-links{display:none;}.hero h1{font-size:36px;}.stats{gap:30px;}.features,.tools-section,.endpoints{padding:60px 20px;}footer{flex-direction:column;text-align:center;}}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="nav">
    <div class="nav-logo">
        <span>😈</span>
        <strong>Devil AI</strong>
    </div>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#tools">Tools</a>
        <a href="#endpoints">API</a>
        <a href="docs/">Docs</a>
        <a href="admin/">Admin</a>
        <a href="docs/#get-key" class="nav-btn">Get API Key</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div>
        <div class="hero-badge">
            <span class="status-dot"></span>
            <?= $online ? count($models) . ' models online' : 'API online' ?>
            &nbsp;·&nbsp; aiapi.devilpvt.in
        </div>

        <h1>The <span>Devil AI</span><br>API Platform</h1>
        <p>Production-ready AI API with real-time tool calling — weather, news, GitHub, Reddit &amp; more. Built by David at Devil One Pvt Ltd.</p>

        <div class="hero-btns">
            <a href="docs/" class="btn btn-red">📚 View Docs</a>
            <a href="docs/#get-key" class="btn btn-outline">🔑 Get API Key</a>
        </div>

        <div class="code-preview">
<span class="cm"># Quick start</span>
<br><span class="kw">curl</span> -X POST <span class="str"><?= $domain ?>/api/v1/chat</span> \
<br>&nbsp;&nbsp;-H <span class="str">"X-API-Key: dk_live_..."</span> \
<br>&nbsp;&nbsp;-d <span class="str">'{ "prompt": "Weather in Kolkata?" }'</span>
<br><br><span class="cm"># Response</span>
<br>{ <span class="key">"response"</span>: <span class="str">"34°C, Partly Cloudy 🌤️"</span>,
<br>&nbsp;&nbsp;<span class="key">"tools_used"</span>: [<span class="str">"weather"</span>] }
        </div>
    </div>
</section>

<!-- Stats -->
<div class="stats">
    <div class="stat"><div class="stat-num"><?= count($models) ?>+</div><div class="stat-lbl">AI Models</div></div>
    <div class="stat"><div class="stat-num">6+</div><div class="stat-lbl">Built-in Tools</div></div>
    <div class="stat"><div class="stat-num">0ms</div><div class="stat-lbl">Added Latency</div></div>
    <div class="stat"><div class="stat-num">∞</div><div class="stat-lbl">Integrations</div></div>
</div>

<!-- Features -->
<section class="features" id="features">
    <div class="section-title">
        <h2>Everything You Need</h2>
        <p>A complete API platform — not just a proxy</p>
    </div>
    <div class="features-grid">
        <div class="feature-card"><div class="feature-icon">🔑</div><h3>API Key Auth</h3><p>Per-key rate limiting, daily quotas, enable/disable — full control over who uses your API.</p></div>
        <div class="feature-card"><div class="feature-icon">🛠️</div><h3>Real-Time Tools</h3><p>Auto-inject live data into every prompt — weather, news, GitHub repos, Reddit, web search and current datetime.</p></div>
        <div class="feature-card"><div class="feature-icon">🤖</div><h3>Auto Detection</h3><p>No need to specify tools manually. The gateway reads your prompt and calls the right tool automatically.</p></div>
        <div class="feature-card"><div class="feature-icon">📊</div><h3>Analytics</h3><p>Every request logged with model, tools used, response time, and IP. View everything from the admin panel.</p></div>
        <div class="feature-card"><div class="feature-icon">🛡️</div><h3>Private Ollama</h3><p>Your Ollama URL is never exposed to clients. All traffic flows through the gateway — zero direct access.</p></div>
        <div class="feature-card"><div class="feature-icon">🌍</div><h3>Any Language</h3><p>PHP, JavaScript, Python, Kotlin, Flutter — full SDK examples in the documentation portal.</p></div>
    </div>
</section>

<!-- Tools -->
<section class="tools-section" id="tools">
    <div class="tools-inner">
        <div class="section-title">
            <h2>Built-in Tools</h2>
            <p>Real-time data injected into every AI response automatically</p>
        </div>
        <div class="tools-grid">
            <div class="tool-badge"><div class="ti">🌦️</div><div class="tn">Weather</div><div class="tk">wttr.in</div></div>
            <div class="tool-badge"><div class="ti">📰</div><div class="tn">News</div><div class="tk">Google RSS</div></div>
            <div class="tool-badge"><div class="ti">🐙</div><div class="tn">GitHub</div><div class="tk">Search API</div></div>
            <div class="tool-badge"><div class="ti">🔍</div><div class="tn">Web Search</div><div class="tk">DuckDuckGo</div></div>
            <div class="tool-badge"><div class="ti">👾</div><div class="tn">Reddit</div><div class="tk">JSON API</div></div>
            <div class="tool-badge"><div class="ti">🕐</div><div class="tn">Date/Time</div><div class="tk">Server Time</div></div>
        </div>
    </div>
</section>

<!-- Endpoints -->
<section class="endpoints" id="endpoints">
    <div class="section-title" style="text-align:left;margin-bottom:30px;">
        <h2>API Endpoints</h2>
        <p style="color:#666;font-size:14px;">Base URL: <code style="color:#ff6666"><?= $domain ?></code></p>
    </div>
    <div class="ep-card">
        <div class="ep-header"><span class="method post">POST</span><span class="ep-url">/api/v1/chat</span></div>
        <div class="ep-desc">Send a prompt and get an AI response. Auto-calls tools based on prompt content. Requires <code>X-API-Key</code> header.</div>
    </div>
    <div class="ep-card">
        <div class="ep-header"><span class="method get">GET</span><span class="ep-url">/api/v1/models</span></div>
        <div class="ep-desc">Returns all available AI models from your Ollama instance. No authentication required.</div>
    </div>
    <div class="ep-card">
        <div class="ep-header"><span class="method post">POST</span><span class="ep-url">/api/v1/keys/create</span></div>
        <div class="ep-desc">Self-service API key generation. Provide name + email to receive a key instantly.</div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <h2>Ready to Build?</h2>
    <p>Get your API key and start integrating Devil AI in minutes.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a href="docs/" class="btn btn-red">📚 Full Documentation</a>
        <a href="docs/#get-key" class="btn btn-outline">🔑 Get Free API Key</a>
    </div>
</section>

<!-- Footer -->
<footer>
    <p>😈 Devil AI API Platform &nbsp;·&nbsp; Built by <strong>David</strong> &nbsp;·&nbsp; Devil One Pvt Ltd &nbsp;·&nbsp; Kolkata, India</p>
    <div class="footer-links">
        <a href="docs/">Docs</a>
        <a href="admin/">Admin</a>
        <a href="https://devilone.in" target="_blank">devilone.in</a>
        <a href="https://github.com/david0154" target="_blank">GitHub</a>
    </div>
</footer>

</body>
</html>
