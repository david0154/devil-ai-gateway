<?php
require_once __DIR__ . '/config.php';

$route = trim($_GET['route'] ?? '', '/');

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
    case 'keys/validate':
        require __DIR__ . '/validate.php';
        break;
    case 'plans':
        require __DIR__ . '/plans.php';
        break;
    default:
        jsonResponse([
            'name' => 'Devil AI Gateway',
            'version' => '2.0.0',
            'status' => 'running',
            'base_url' => GATEWAY_DOMAIN,
            'docs' => GATEWAY_DOMAIN . '/docs/',
            'endpoints' => [
                'POST /api/v1/chat' => 'Chat with Devil AI',
                'GET /api/v1/models' => 'List available models',
                'POST /api/v1/keys/create' => 'Generate client API key',
                'POST /api/v1/keys/validate' => 'Validate API key and subscription',
                'GET /api/v1/plans' => 'Get pricing and speed plans'
            ]
        ]);
}
