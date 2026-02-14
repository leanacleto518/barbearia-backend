<?php
/**
 * ========================================
 * BARBEARIA BRUM - LISTAR AGENDAMENTOS
 * Endpoint para visualizar agendamentos salvos
 * ========================================
 */

// Configurações de segurança
header('Content-Type: application/json; charset=utf-8');

// CORS
$default_origins = [
    'https://leanacleto518.github.io',
    'http://localhost:5500',
    'http://127.0.0.1:5500'
];

$env = getenv('ALLOWED_ORIGINS');
if ($env !== false && strlen(trim($env)) > 0) {
    $allowed_origins = array_map('trim', explode(',', $env));
} else {
    $allowed_origins = $default_origins;
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array('*', $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($origin && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: ' . ($allowed_origins[0] ?? '*'));
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responde a requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Apenas GET permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido. Use GET.'
    ]);
    exit();
}

// Configurações
$arquivo_csv = __DIR__ . '/data/agendamentos.csv';

// Verifica se arquivo existe
if (!file_exists($arquivo_csv)) {
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Nenhum agendamento encontrado',
        'total' => 0,
        'agendamentos' => []
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Parâmetro para download
$download = isset($_GET['download']) && $_GET['download'] === 'true';

if ($download) {
    // Download do arquivo CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="agendamentos_' . date('Y-m-d_His') . '.csv"');
    readfile($arquivo_csv);
    exit();
}

// Lê e retorna os agendamentos em JSON
try {
    $agendamentos = [];
    $handle = fopen($arquivo_csv, 'r');
    
    if ($handle) {
        // Lê cabeçalho
        $cabecalho = fgetcsv($handle, 1000, ';');
        
        // Lê dados
        while (($linha = fgetcsv($handle, 1000, ';')) !== FALSE) {
            if (count($linha) >= count($cabecalho)) {
                $agendamento = [];
                foreach ($cabecalho as $index => $campo) {
                    $agendamento[$campo] = $linha[$index] ?? '';
                }
                $agendamentos[] = $agendamento;
            }
        }
        
        fclose($handle);
    }
    
    // Retorna JSON
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Agendamentos recuperados com sucesso',
        'total' => count($agendamentos),
        'agendamentos' => $agendamentos,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao ler agendamentos',
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
