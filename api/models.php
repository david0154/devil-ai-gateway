<?php
// ============================================
// Devil AI Gateway — /v1/models endpoint
// ============================================

require_once __DIR__ . '/config.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, OLLAMA_URL . '/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(['error' => 'Model server unreachable']);
    exit;
}

$data   = json_decode($response, true);
$models = array_map(fn($m) => [
    'id'       => $m['name'],
    'provider' => 'Devil AI',
    'status'   => 'available'
], $data['models'] ?? []);

echo json_encode([
    'models'  => $models,
    'powered_by' => 'Ollama @ ' . OLLAMA_URL
]);
