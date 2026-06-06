<?php
// ============================================
// Devil AI Gateway — Main Router
// ============================================

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'chat':
        require __DIR__ . '/chat.php';
        break;
    case 'models':
        require __DIR__ . '/models.php';
        break;
    case 'keys/create':
        require __DIR__ . '/keys.php';
        break;
    default:
        echo json_encode([
            'name'    => 'Devil AI Gateway',
            'version' => '1.0.0',
            'status'  => 'running',
            'docs'    => GATEWAY_DOMAIN . '/docs/',
            'endpoints' => [
                'POST /api/v1/chat'         => 'Send a prompt to Devil AI',
                'GET  /api/v1/models'       => 'List available models',
                'POST /api/v1/keys/create'  => 'Generate a new API key'
            ]
        ]);
}
