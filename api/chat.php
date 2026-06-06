<?php
// ============================================
// Devil AI Gateway — /v1/chat endpoint
// ============================================

require_once __DIR__ . '/config.php';

// --- Validate API Key ---
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing X-API-Key header']);
    exit;
}

try {
    $db = getDB();

    // Reset daily count if new day
    $db->prepare("UPDATE api_keys SET requests_today=0 WHERE last_reset < CURDATE() OR last_reset IS NULL")->execute();
    $db->prepare("UPDATE api_keys SET last_reset=CURDATE() WHERE last_reset < CURDATE() OR last_reset IS NULL")->execute();

    $stmt = $db->prepare("SELECT * FROM api_keys WHERE api_key=? AND is_active=1");
    $stmt->execute([$apiKey]);
    $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$keyData) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or inactive API key']);
        exit;
    }

    if ($keyData['requests_today'] >= $keyData['limit_per_day']) {
        http_response_code(429);
        echo json_encode(['error' => 'Daily rate limit exceeded', 'limit' => $keyData['limit_per_day']]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// --- Parse Request Body ---
$body   = json_decode(file_get_contents('php://input'), true);
$prompt = trim($body['prompt'] ?? '');
$model  = $body['model']  ?? 'devil-ai';
$tools  = $body['tools']  ?? null; // null = auto-detect

if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['error' => 'prompt is required']);
    exit;
}

// --- Tool Detection ---
if ($tools === null) {
    $tools = autoDetectTools($prompt);
}

// --- Execute Tools ---
$toolContext = '';
foreach ($tools as $tool) {
    switch ($tool) {
        case 'weather':    $toolContext .= getWeatherContext($prompt);  break;
        case 'news':       $toolContext .= getNewsContext($prompt);      break;
        case 'github':     $toolContext .= getGitHubContext($prompt);    break;
        case 'web_search': $toolContext .= getDDGContext($prompt);       break;
        case 'reddit':     $toolContext .= getRedditContext($prompt);    break;
        case 'datetime':   $toolContext .= getDateTimeContext();         break;
    }
}

// --- Build Enriched Prompt ---
$finalPrompt = $toolContext
    ? "You have access to the following real-time data:\n\n{$toolContext}\nAnswer the user's question using this data.\nUser: {$prompt}"
    : $prompt;

// --- Call Ollama ---
$startTime = microtime(true);

$ollamaData = ['model' => $model, 'prompt' => $finalPrompt, 'stream' => false];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, OLLAMA_URL . '/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollamaData));
$ollamaResponse = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

$responseTimeMs = round((microtime(true) - $startTime) * 1000);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'Model unreachable: ' . $curlError]);
    exit;
}

$ollamaJson = json_decode($ollamaResponse, true);
$aiResponse  = $ollamaJson['response'] ?? 'No response from model.';

// --- Update Usage Stats ---
try {
    $db->prepare("
        UPDATE api_keys
        SET requests_today = requests_today + 1,
            total_requests = total_requests + 1
        WHERE api_key = ?
    ")->execute([$apiKey]);

    $db->prepare("
        INSERT INTO request_logs
        (api_key, model, prompt, tools_used, response_time_ms, status, ip_address)
        VALUES (?, ?, ?, ?, ?, 'success', ?)
    ")->execute([
        $apiKey, $model,
        substr($prompt, 0, 500),
        implode(',', $tools),
        $responseTimeMs,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
} catch (Exception $e) { /* log silently */ }

// --- Return Response ---
echo json_encode([
    'response'    => $aiResponse,
    'model'       => $model,
    'tools_used'  => $tools,
    'usage' => [
        'requests_today' => $keyData['requests_today'] + 1,
        'limit_per_day'  => $keyData['limit_per_day'],
        'response_ms'    => $responseTimeMs
    ]
]);

// ============================================
// TOOL FUNCTIONS
// ============================================

function autoDetectTools($prompt) {
    $tools = [];
    if (preg_match('/weather|temperature|rain|forecast|humid|wind/i', $prompt))   $tools[] = 'weather';
    if (preg_match('/news|latest|breaking|headline|today|happened/i', $prompt))   $tools[] = 'news';
    if (preg_match('/github|repo|repository|code|library|package/i', $prompt))    $tools[] = 'github';
    if (preg_match('/reddit|community|discussion|post|subreddit/i', $prompt))     $tools[] = 'reddit';
    if (preg_match('/\bdate\b|\btime\b|\bday\b|\byear\b|\bnow\b|today/i', $prompt)) $tools[] = 'datetime';
    if (empty($tools) && preg_match('/search|find|who is|what is|how to|define/i', $prompt)) $tools[] = 'web_search';
    return $tools;
}

function getWeatherContext($prompt) {
    preg_match('/weather\s+(?:in\s+)?([a-zA-Z\s,]+?)(?:\?|$|today|now)/i', $prompt, $m);
    $city = isset($m[1]) ? trim($m[1]) : 'Kolkata';
    $url  = 'https://wttr.in/' . urlencode($city) . '?format=j1';
    $data = @json_decode(@file_get_contents($url), true);
    if (!$data) return '';
    $c    = $data['current_condition'][0] ?? [];
    $temp = $c['temp_C'] ?? 'N/A';
    $desc = $c['weatherDesc'][0]['value'] ?? '';
    $hum  = $c['humidity'] ?? '';
    return "[Weather in {$city}]: {$temp}°C, {$desc}, Humidity: {$hum}%\n";
}

function getNewsContext($prompt) {
    preg_match('/news\s+(?:about\s+)?(.+?)(?:\?|$)/i', $prompt, $m);
    $query = isset($m[1]) ? trim($m[1]) : $prompt;
    $url   = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=en-IN&gl=IN&ceid=IN:en';
    $xml   = @simplexml_load_file($url);
    if (!$xml) return '';
    $items = [];
    foreach (array_slice((array)($xml->channel->item ?? []), 0, 4) as $item) {
        $items[] = (string)($item->title ?? '');
    }
    return $items ? '[Latest News]: ' . implode(' | ', $items) . "\n" : '';
}

function getGitHubContext($prompt) {
    preg_match('/(?:github|repo|library|package)\s+(?:for\s+)?(.+?)(?:\?|$)/i', $prompt, $m);
    $query = isset($m[1]) ? trim($m[1]) : $prompt;
    $url   = 'https://api.github.com/search/repositories?q=' . urlencode($query) . '&per_page=3&sort=stars';
    $opts  = ['http' => ['header' => "User-Agent: DevilAI-Gateway/1.0\r\n"]];
    $data  = @json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
    if (!$data || empty($data['items'])) return '';
    $repos = array_map(fn($r) => "{$r['full_name']} ⭐{$r['stargazers_count']} — {$r['description']}", $data['items']);
    return '[GitHub Top Repos]: ' . implode(' | ', $repos) . "\n";
}

function getDDGContext($prompt) {
    $url  = 'https://api.duckduckgo.com/?q=' . urlencode($prompt) . '&format=json&no_redirect=1&no_html=1';
    $data = @json_decode(@file_get_contents($url), true);
    $text = $data['AbstractText'] ?? $data['Answer'] ?? '';
    return $text ? "[Web Search]: {$text}\n" : '';
}

function getRedditContext($prompt) {
    $url  = 'https://www.reddit.com/search.json?q=' . urlencode($prompt) . '&sort=hot&limit=3';
    $opts = ['http' => ['header' => "User-Agent: DevilAI-Gateway/1.0\r\n"]];
    $data = @json_decode(@file_get_contents($url, false, stream_context_create($opts)), true);
    $posts = $data['data']['children'] ?? [];
    if (!$posts) return '';
    $titles = array_map(fn($p) => $p['data']['title'] ?? '', array_slice($posts, 0, 3));
    return '[Reddit Discussions]: ' . implode(' | ', $titles) . "\n";
}

function getDateTimeContext() {
    return '[Current Date/Time]: ' . date('l, d F Y H:i:s T') . "\n";
}
