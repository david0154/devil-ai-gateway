<?php
require_once __DIR__ . '/../api/config.php';
$domain = GATEWAY_DOMAIN;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devil AI API — Developer Documentation</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0a0a0a;color:#e0e0e0;}
.sidebar{position:fixed;left:0;top:0;width:260px;height:100vh;background:#111;border-right:1px solid #222;overflow-y:auto;padding:20px 0;}
.sidebar-logo{padding:0 20px 20px;border-bottom:1px solid #222;}
.sidebar-logo h2{color:#ff4444;font-size:18px;} .sidebar-logo p{color:#666;font-size:12px;}
.sidebar-nav{padding:15px 0;}
.sidebar-nav a{display:block;padding:8px 20px;color:#aaa;text-decoration:none;font-size:14px;border-left:3px solid transparent;transition:all .2s;}
.sidebar-nav a:hover,.sidebar-nav a.active{color:#ff4444;border-left-color:#ff4444;background:#1a1a1a;}
.sidebar-nav .section{padding:15px 20px 5px;color:#555;font-size:11px;text-transform:uppercase;letter-spacing:1px;}
main{margin-left:260px;padding:40px;max-width:900px;}
h1{font-size:32px;color:#ff4444;margin-bottom:10px;}
.subtitle{color:#888;margin-bottom:40px;}
h2{font-size:22px;color:#ff4444;margin:40px 0 15px;border-bottom:1px solid #222;padding-bottom:10px;}
h3{font-size:16px;color:#ccc;margin:25px 0 10px;}
.badge{display:inline-block;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:bold;margin-right:8px;}
.post{background:#ff4444;color:#fff;} .get{background:#00aa66;color:#fff;}
.endpoint{background:#111;border:1px solid #333;border-radius:8px;padding:20px;margin:15px 0;}
.endpoint-url{font-family:monospace;color:#00ff88;font-size:14px;}
pre{background:#0d0d0d;border:1px solid #222;border-radius:6px;padding:16px;overflow-x:auto;font-size:13px;line-height:1.5;}
code{font-family:'Fira Code',monospace;}
.tag{display:inline-block;background:#1a1a1a;border:1px solid #333;padding:3px 10px;border-radius:4px;font-size:12px;color:#aaa;margin:3px;}
.try-box{background:#111;border:1px solid #ff4444;border-radius:8px;padding:20px;margin:20px 0;}
.try-box input,.try-box textarea,.try-box select{width:100%;padding:10px;background:#0a0a0a;border:1px solid #333;color:#fff;border-radius:5px;font-family:monospace;font-size:13px;margin:5px 0 12px;}
.try-box button{background:#ff4444;color:#fff;border:none;padding:10px 25px;cursor:pointer;border-radius:5px;font-size:14px;}
.response-box{background:#0d0d0d;border:1px solid #333;border-radius:5px;padding:15px;margin-top:15px;font-family:monospace;font-size:13px;min-height:80px;white-space:pre-wrap;color:#00ff88;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#1a1a1a;color:#aaa;padding:10px;text-align:left;font-weight:500;}
td{padding:10px;border-bottom:1px solid #1a1a1a;}
.param{color:#ff9944;font-family:monospace;} .type{color:#4499ff;} .desc{color:#888;}
.alert{background:#1a2a1a;border:1px solid #2a4a2a;border-radius:6px;padding:15px;margin:20px 0;color:#aaffaa;}
.key-result{background:#1a1a2a;border:1px solid #4444ff;border-radius:8px;padding:20px;display:none;margin-top:15px;}
.key-display{font-family:monospace;font-size:16px;color:#00ff88;word-break:break-all;padding:10px;background:#0a0a0a;border-radius:4px;}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>😈 Devil AI API</h2>
        <p>Developer Documentation v1.0</p>
    </div>
    <nav class="sidebar-nav">
        <div class="section">Getting Started</div>
        <a href="#overview" class="active">Overview</a>
        <a href="#quickstart">Quick Start</a>
        <a href="#auth">Authentication</a>
        <a href="#get-key">Get API Key</a>
        <div class="section">API Reference</div>
        <a href="#chat">POST /v1/chat</a>
        <a href="#models">GET /v1/models</a>
        <div class="section">Features</div>
        <a href="#tools">Tool Calling</a>
        <a href="#auto-detect">Auto-Detection</a>
        <div class="section">Integrations</div>
        <a href="#php">PHP</a>
        <a href="#js">JavaScript</a>
        <a href="#python">Python</a>
        <a href="#kotlin">Android/Kotlin</a>
        <a href="#flutter">Flutter</a>
        <div class="section">Links</div>
        <a href="https://devilone.in" target="_blank">devilone.in</a>
        <a href="https://github.com/david0154" target="_blank">GitHub</a>
    </nav>
</div>

<main>

<section id="overview">
<h1>😈 Devil AI API</h1>
<p class="subtitle">Powered by Ollama · Built by David · Devil One Pvt Ltd</p>

<div class="alert">
    🚀 <strong>Base URL:</strong> <code><?= $domain ?></code><br>
    📡 All requests require an <code>X-API-Key</code> header.
</div>

<p>Devil AI Gateway is a full-featured PHP API gateway that wraps your private Ollama instance with API key authentication, rate limiting, tool calling (weather, news, GitHub, Reddit, web search, datetime), and this developer portal.</p>
</section>

<section id="quickstart">
<h2>⚡ Quick Start</h2>
<pre><code>curl -X POST <?= $domain ?>/api/v1/chat \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "model": "devil-ai",
    "prompt": "What is the weather in Kolkata today?"
  }'</code></pre>
</section>

<section id="auth">
<h2>🔐 Authentication</h2>
<p>Every request must include your API key in the <code>X-API-Key</code> header.</p>
<pre><code>X-API-Key: dk_live_your_key_here</code></pre>

<div class="table-wrap">
<table>
<tr><th>Status Code</th><th>Meaning</th></tr>
<tr><td>200</td><td>Success</td></tr>
<tr><td>401</td><td>Missing or invalid API key</td></tr>
<tr><td>429</td><td>Daily rate limit exceeded</td></tr>
<tr><td>502</td><td>AI model server unreachable</td></tr>
</table>
</div>
</section>

<section id="get-key">
<h2>🔑 Get Your API Key</h2>
<div class="try-box">
    <h3>Request a Free API Key</h3>
    <label>Your Name</label>
    <input type="text" id="kname" placeholder="David">
    <label>Your Email</label>
    <input type="email" id="kemail" placeholder="you@example.com">
    <button onclick="requestKey()">Generate API Key</button>
    <div class="key-result" id="keyResult">
        <p style="color:#aaa;margin-bottom:10px">Your API Key:</p>
        <div class="key-display" id="keyDisplay"></div>
        <p style="color:#888;font-size:12px;margin-top:10px">Save this key securely. It will not be shown again.</p>
    </div>
</div>
</section>

<section id="chat">
<h2>POST /v1/chat</h2>
<div class="endpoint">
    <span class="badge post">POST</span>
    <span class="endpoint-url"><?= $domain ?>/api/v1/chat</span>
</div>

<h3>Request Body</h3>
<div class="table-wrap">
<table>
<tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr>
<tr><td class="param">model</td><td class="type">string</td><td>No</td><td class="desc">Model to use. Default: <code>devil-ai</code></td></tr>
<tr><td class="param">prompt</td><td class="type">string</td><td><strong>Yes</strong></td><td class="desc">Your message or question</td></tr>
<tr><td class="param">tools</td><td class="type">array</td><td>No</td><td class="desc">Tools to use. Omit for auto-detection</td></tr>
</table>
</div>

<h3>Example Request</h3>
<pre><code>{
  "model": "devil-ai",
  "prompt": "What is the weather in Kolkata?",
  "tools": ["weather", "datetime"]
}</code></pre>

<h3>Example Response</h3>
<pre><code>{
  "response": "The current weather in Kolkata is 34°C, Partly Cloudy with 72% humidity.",
  "model": "devil-ai",
  "tools_used": ["weather", "datetime"],
  "usage": {
    "requests_today": 5,
    "limit_per_day": 100,
    "response_ms": 1243
  }
}</code></pre>

<h3>🧪 Live Playground</h3>
<div class="try-box">
    <label>API Key</label>
    <input type="text" id="playKey" placeholder="dk_live_...">
    <label>Model</label>
    <select id="playModel"><option value="devil-ai">devil-ai</option><option value="gemma3:4b">gemma3:4b</option></select>
    <label>Prompt</label>
    <textarea id="playPrompt" rows="3" placeholder="Ask anything...">What is the weather in Kolkata today?</textarea>
    <button onclick="sendPlayground()">🚀 Send</button>
    <div class="response-box" id="playResponse">Response will appear here...</div>
</div>
</section>

<section id="models">
<h2>GET /v1/models</h2>
<div class="endpoint">
    <span class="badge get">GET</span>
    <span class="endpoint-url"><?= $domain ?>/api/v1/models</span>
</div>
<p>Returns all available models. No API key required.</p>
<pre><code>curl <?= $domain ?>/api/v1/models</code></pre>
<pre><code>{
  "models": [
    {"id": "devil-ai:latest", "provider": "Devil AI", "status": "available"},
    {"id": "gemma3:4b",       "provider": "Devil AI", "status": "available"}
  ],
  "powered_by": "Ollama @ https://aiapi.devilpvt.in"
}</code></pre>
</section>

<section id="tools">
<h2>🛠️ Available Tools</h2>
<div class="table-wrap">
<table>
<tr><th>Tool Name</th><th>Trigger Keywords (auto)</th><th>Data Source</th><th>Example Use</th></tr>
<tr><td class="param">weather</td><td>weather, temperature, rain, forecast</td><td>wttr.in</td><td>"Weather in Mumbai?"</td></tr>
<tr><td class="param">news</td><td>news, latest, breaking, headline</td><td>Google News RSS</td><td>"Latest AI news"</td></tr>
<tr><td class="param">github</td><td>github, repo, code, library, package</td><td>GitHub Search API</td><td>"Best PHP repo for auth"</td></tr>
<tr><td class="param">web_search</td><td>search, find, who is, what is, how to</td><td>DuckDuckGo</td><td>"Who is Elon Musk?"</td></tr>
<tr><td class="param">reddit</td><td>reddit, community, discussion</td><td>Reddit JSON API</td><td>"Reddit discussion on AI"</td></tr>
<tr><td class="param">datetime</td><td>date, time, day, year, now, today</td><td>Server PHP date()</td><td>"What day is today?"</td></tr>
</table>
</div>
</section>

<section id="auto-detect">
<h2>🤖 Auto Tool Detection</h2>
<p>If you omit the <code>tools</code> parameter, the gateway automatically detects which tools to call based on your prompt. No manual configuration needed.</p>
<pre><code>// Tools array omitted — gateway decides automatically
{
  "model": "devil-ai",
  "prompt": "What is the latest news about AI and today's date?"
}
// Gateway will auto-call: ["news", "datetime"]</code></pre>
</section>

<section id="php">
<h2>PHP Integration</h2>
<pre><code>&lt;?php
function askDevilAI($prompt, $apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL           => "<?= $domain ?>/api/v1/chat",
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_POST          => true,
        CURLOPT_HTTPHEADER    => [
            "Content-Type: application/json",
            "X-API-Key: $apiKey"
        ],
        CURLOPT_POSTFIELDS    => json_encode([
            "model"  => "devil-ai",
            "prompt" => $prompt
        ])
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true)['response'] ?? '';
}

$answer = askDevilAI("What is the weather in Delhi?", "dk_live_...");
echo $answer;</code></pre>
</section>

<section id="js">
<h2>JavaScript Integration</h2>
<pre><code>async function askDevilAI(prompt, apiKey) {
    const res = await fetch("<?= $domain ?>/api/v1/chat", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-API-Key": apiKey
        },
        body: JSON.stringify({ model: "devil-ai", prompt })
    });
    const data = await res.json();
    return data.response;
}

// Usage
const answer = await askDevilAI("Latest tech news?", "dk_live_...");
console.log(answer);</code></pre>
</section>

<section id="python">
<h2>Python Integration</h2>
<pre><code>import requests

def ask_devil_ai(prompt, api_key):
    response = requests.post(
        "<?= $domain ?>/api/v1/chat",
        headers={
            "Content-Type": "application/json",
            "X-API-Key": api_key
        },
        json={
            "model": "devil-ai",
            "prompt": prompt
        }
    )
    return response.json().get("response", "")

answer = ask_devil_ai("What time is it in India?", "dk_live_...")
print(answer)</code></pre>
</section>

<section id="kotlin">
<h2>Android / Kotlin Integration</h2>
<pre><code>// Retrofit interface
interface DevilAIService {
    @POST("api/v1/chat")
    suspend fun chat(
        @Header("X-API-Key") apiKey: String,
        @Body request: ChatRequest
    ): ChatResponse
}

data class ChatRequest(val model: String, val prompt: String)
data class ChatResponse(val response: String, val tools_used: List&lt;String&gt;)

// Retrofit instance
val retrofit = Retrofit.Builder()
    .baseUrl("<?= $domain ?>/")
    .addConverterFactory(GsonConverterFactory.create())
    .build()

// Usage in ViewModel
val response = service.chat("dk_live_...", ChatRequest("devil-ai", "Hello!"))
println(response.response)</code></pre>
</section>

<section id="flutter">
<h2>Flutter Integration</h2>
<pre><code>import 'dart:convert';
import 'package:http/http.dart' as http;

Future&lt;String&gt; askDevilAI(String prompt, String apiKey) async {
    final response = await http.post(
        Uri.parse("<?= $domain ?>/api/v1/chat"),
        headers: {
            "Content-Type": "application/json",
            "X-API-Key": apiKey,
        },
        body: jsonEncode({
            "model": "devil-ai",
            "prompt": prompt,
        }),
    );
    final data = jsonDecode(response.body);
    return data["response"] ?? "";
}

// Usage
final answer = await askDevilAI("Weather in Kolkata?", "dk_live_...");
print(answer);</code></pre>
</section>

</main>

<script>
async function sendPlayground() {
    const key    = document.getElementById('playKey').value.trim();
    const model  = document.getElementById('playModel').value;
    const prompt = document.getElementById('playPrompt').value.trim();
    const box    = document.getElementById('playResponse');

    if (!key || !prompt) { box.textContent = 'Please enter API key and prompt.'; return; }
    box.textContent = 'Thinking...';

    try {
        const res = await fetch('<?= $domain ?>/api/v1/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': key },
            body: JSON.stringify({ model, prompt })
        });
        const data = await res.json();
        box.textContent = data.response
            ? `${data.response}\n\n[Tools: ${(data.tools_used||[]).join(', ')||'none'} | ${data.usage?.response_ms}ms]`
            : JSON.stringify(data, null, 2);
    } catch(e) { box.textContent = 'Error: ' + e.message; }
}

async function requestKey() {
    const name  = document.getElementById('kname').value.trim();
    const email = document.getElementById('kemail').value.trim();
    if (!name || !email) { alert('Please enter name and email'); return; }

    try {
        const res  = await fetch('<?= $domain ?>/api/v1/keys/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, limit_per_day: 100 })
        });
        const data = await res.json();
        if (data.api_key) {
            document.getElementById('keyDisplay').textContent = data.api_key;
            document.getElementById('keyResult').style.display = 'block';
        } else {
            alert(data.error || 'Error generating key');
        }
    } catch(e) { alert('Error: ' + e.message); }
}

// Smooth scrolling for sidebar
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        document.querySelector(a.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth' });
        document.querySelectorAll('.sidebar-nav a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
    });
});
</script>

</body>
</html>
