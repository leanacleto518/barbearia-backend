<?php
/**
 * ========================================
 * BARBEARIA BRUM - BACKEND API
 * Arquivo principal para Render.com
 * ========================================
 */

header('Content-Type: application/json; charset=utf-8');

// CORS configurável via variável de ambiente (mantém compatibilidade)
$env = getenv('ALLOWED_ORIGINS');
$allowed = [];
if ($env !== false && strlen(trim($env)) > 0) {
    $allowed = array_map('trim', explode(',', $env));
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array('*', $allowed, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origin && in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://leanacleto518.github.io');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responde a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Informações da API
$info = [
    'nome' => 'Barbearia Brum - Backend API',
    'versao' => '1.0.0',
    'status' => 'online',
    'timestamp' => date('c'),
    'servidor' => 'Render.com',
    'endpoints' => [
        'GET /' => 'Informações da API',
        'POST /agendamento-online.php' => 'Criar novo agendamento',
        'GET /listar-agendamentos.php' => 'Listar todos os agendamentos (JSON)',
        'GET /listar-agendamentos.php?download=true' => 'Baixar agendamentos (CSV)',
        'GET /health' => 'Health check do servidor'
    ],
    'recursos' => [
        'CORS configurado para GitHub Pages',
        'Rate limiting por IP',
        'Validação completa de dados',
        'Armazenamento em CSV',
        'Proteção contra spam'
    ]
];

// Health check endpoint
if (isset($_GET['health']) || $_SERVER['REQUEST_URI'] === '/health') {
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'uptime' => 'OK',
        'database' => file_exists(__DIR__ . '/data') ? 'OK' : 'Initializing'
    ]);
    exit();
}

// Resposta padrão
echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>