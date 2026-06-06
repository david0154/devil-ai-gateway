<?php
require_once __DIR__ . '/config.php';

try {
    $db = getDB();
    ensureRequestCountersReset($db);
} catch (Exception $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!$apiKey) {
    jsonResponse(['error' => 'Missing X-API-Key header'], 401);
}

$stmt = $db->prepare("SELECT * FROM api_keys WHERE api_key = ? LIMIT 1");
$stmt->execute([$apiKey]);
$keyData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$keyData || intval($keyData['is_active']) !== 1) {
    jsonResponse(['error' => 'Invalid or inactive API key'], 401);
}

if (!empty($keyData['expires_at']) && strtotime($keyData['expires_at']) < time()) {
    jsonResponse([
        'error' => 'API key expired',
        'expired_at' => $keyData['expires_at']
    ], 403);
}

$bucket = currentMinuteBucket();
if (($keyData['minute_bucket'] ?? '') !== $bucket) {
    $db->prepare("UPDATE api_keys SET requests_this_minute = 0, minute_bucket = ? WHERE id = ?")->execute([$bucket, $keyData['id']]);
    $keyData['requests_this_minute'] = 0;
    $keyData['minute_bucket'] = $bucket;
}

if (intval($keyData['requests_today']) >= intval($keyData['limit_per_day'])) {
    jsonResponse([
        'error' => 'Daily rate limit exceeded',
        'limit_per_day' => intval($keyData['limit_per_day'])
    ], 429);
}

if (intval($keyData['requests_this_minute']) >= intval($keyData['rpm_limit'])) {
    jsonResponse([
        'error' => 'Per-minute rate limit exceeded',
        'rpm_limit' => intval($keyData['rpm_limit']),
        'minute_bucket' => $bucket
    ], 429);
}

$body = getJsonBody();
$prompt = trim($body['prompt'] ?? '');
$model = trim($body['model'] ?? 'devil-ai');
$tools = $body['tools'] ?? null;
if ($prompt === '') {
    jsonResponse(['error' => 'prompt is required'], 400);
}

if ($tools === null) {
    $tools = autoDetectTools($prompt);
}
if (!is_array($tools)) $tools = [];

$toolContext = '';
foreach ($tools as $tool) {
    switch ($tool) {
        case 'weather':    $toolContext .= getWeatherContext($prompt); break;
        case 'news':       $toolContext .= getNewsContext($prompt); break;
        case 'github':     $toolContext .= getGitHubContext($prompt); break;
        case 'web_search': $toolContext .= getDDGContext($prompt); break;
        case 'reddit':     $toolContext .= getRedditContext($prompt); break;
        case 'datetime':   $toolContext .= getDateTimeContext(); break;
    }
}

$finalPrompt = $toolContext
    ? "You have access to the following real-time data:\n\n{$toolContext}\nUse it to answer the user clearly.\nUser: {$prompt}"
    : $prompt;

$timeout = max(10, intval($keyData['timeout_seconds'] ?: 60));
$startTime = microtime(true);
$ollamaData = ['model' => $model, 'prompt' => $finalPrompt, 'stream' => false];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, OLLAMA_URL . '/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollamaData));
$ollamaResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$responseTimeMs = round((microtime(true) - $startTime) * 1000);

if ($curlError || !$ollamaResponse || $httpCode >= 500) {
    $db->prepare("INSERT INTO request_logs (api_key, app_name, client_name, client_email, model, prompt, tools_used, response_time_ms, status, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'error', ?)")
       ->execute([$apiKey, $keyData['app_name'], $keyData['client_name'], $keyData['client_email'], $model, substr($prompt,0,500), implode(',', $tools), $responseTimeMs, clientIp()]);
    jsonResponse(['error' => 'Model server unreachable'], 502);
}

$ollamaJson = json_decode($ollamaResponse, true);
$aiResponse = $ollamaJson['response'] ?? 'No response from model.';

$db->prepare("UPDATE api_keys SET requests_today = requests_today + 1, total_requests = total_requests + 1, requests_this_minute = requests_this_minute + 1, last_used_at = NOW() WHERE id = ?")
   ->execute([$keyData['id']]);

$db->prepare("INSERT INTO request_logs (api_key, app_name, client_name, client_email, model, prompt, tools_used, response_time_ms, status, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'success', ?)")
   ->execute([$apiKey, $keyData['app_name'], $keyData['client_name'], $keyData['client_email'], $model, substr($prompt,0,500), implode(',', $tools), $responseTimeMs, clientIp()]);

jsonResponse([
    'response' => $aiResponse,
    'model' => $model,
    'tools_used' => $tools,
    'client' => [
        'app_name' => $keyData['app_name'],
        'client_name' => $keyData['client_name'],
        'plan_name' => $keyData['plan_name'],
        'speed_tier' => $keyData['speed_tier']
    ],
    'usage' => [
        'requests_today' => intval($keyData['requests_today']) + 1,
        'limit_per_day' => intval($keyData['limit_per_day']),
        'requests_this_minute' => intval($keyData['requests_this_minute']) + 1,
        'rpm_limit' => intval($keyData['rpm_limit']),
        'response_ms' => $responseTimeMs,
        'expires_at' => $keyData['expires_at']
    ]
]);

function autoDetectTools($prompt) {
    $tools = [];
    if (preg_match('/weather|temperature|rain|forecast|humid|wind/i', $prompt)) $tools[] = 'weather';
    if (preg_match('/news|latest|breaking|headline|today|happened/i', $prompt)) $tools[] = 'news';
    if (preg_match('/github|repo|repository|code|library|package/i', $prompt)) $tools[] = 'github';
    if (preg_match('/reddit|community|discussion|post|subreddit/i', $prompt)) $tools[] = 'reddit';
    if (preg_match('/\bdate\b|\btime\b|\bday\b|\byear\b|\bnow\b|today/i', $prompt)) $tools[] = 'datetime';
    if (empty($tools) && preg_match('/search|find|who is|what is|how to|define/i', $prompt)) $tools[] = 'web_search';
    return $tools;
}
function getWeatherContext($prompt) {
    preg_match('/weather\s+(?:in\s+)?([a-zA-Z\s,]+?)(?:\?|$|today|now)/i', $prompt, $m);
    $city = isset($m[1]) ? trim($m[1]) : 'Kolkata';
    $url = 'https://wttr.in/' . urlencode($city) . '?format=j1';
    $data = @json_decode(@file_get_contents($url), true);
    if (!$data) return '';
    $c = $data['current_condition'][0] ?? [];
    return "[Weather in {$city}]: " . ($c['temp_C'] ?? 'N/A') . "°C, " . ($c['weatherDesc'][0]['value'] ?? '') . ", Humidity: " . ($c['humidity'] ?? '') . "%\n";
}
function getNewsContext($prompt) {
    preg_match('/news\s+(?:about\s+)?(.+?)(?:\?|$)/i', $prompt, $m);
    $query = isset($m[1]) ? trim($m[1]) : $prompt;
    $xml = @simplexml_load_file('https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=en-IN&gl=IN&ceid=IN:en');
    if (!$xml) return '';
    $items = [];
    foreach (array_slice((array)($xml->channel->item ?? []), 0, 4) as $item) $items[] = (string)($item->title ?? '');
    return $items ? '[Latest News]: ' . implode(' | ', $items) . "\n" : '';
}
function getGitHubContext($prompt) {
    preg_match('/(?:github|repo|library|package)\s+(?:for\s+)?(.+?)(?:\?|$)/i', $prompt, $m);
    $query = isset($m[1]) ? trim($m[1]) : $prompt;
    $opts = ['http' => ['header' => "User-Agent: DevilAI-Gateway/2.0\r\n"]];
    $data = @json_decode(@file_get_contents('https://api.github.com/search/repositories?q=' . urlencode($query) . '&per_page=3&sort=stars', false, stream_context_create($opts)), true);
    if (!$data || empty($data['items'])) return '';
    $repos = array_map(fn($r) => "{$r['full_name']} ⭐{$r['stargazers_count']} — {$r['description']}", $data['items']);
    return '[GitHub Top Repos]: ' . implode(' | ', $repos) . "\n";
}
function getDDGContext($prompt) {
    $data = @json_decode(@file_get_contents('https://api.duckduckgo.com/?q=' . urlencode($prompt) . '&format=json&no_redirect=1&no_html=1'), true);
    $text = $data['AbstractText'] ?? $data['Answer'] ?? '';
    return $text ? "[Web Search]: {$text}\n" : '';
}
function getRedditContext($prompt) {
    $opts = ['http' => ['header' => "User-Agent: DevilAI-Gateway/2.0\r\n"]];
    $data = @json_decode(@file_get_contents('https://www.reddit.com/search.json?q=' . urlencode($prompt) . '&sort=hot&limit=3', false, stream_context_create($opts)), true);
    $posts = $data['data']['children'] ?? [];
    if (!$posts) return '';
    $titles = array_map(fn($p) => $p['data']['title'] ?? '', array_slice($posts, 0, 3));
    return '[Reddit Discussions]: ' . implode(' | ', $titles) . "\n";
}
function getDateTimeContext() { return '[Current Date/Time]: ' . date('l, d F Y H:i:s T') . "\n"; }
