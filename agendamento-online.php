<?php
/**
 * ========================================
 * BARBEARIA BRUM - SISTEMA DE AGENDAMENTO ONLINE
 * Versão otimizada para hospedagem (Render, Heroku, etc.)
 * ========================================
 */

// Configurações para hospedagem online
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://leanacleto518.github.io');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Responde a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurações do sistema
$config = [
    'arquivo_csv' => __DIR__ . '/data/agendamentos.csv',
    'diretorio_data' => __DIR__ . '/data',
    'timezone' => 'America/Sao_Paulo',
    'formato_data' => 'd/m/Y H:i:s',
    'max_agendamentos' => 1000, // Limite para evitar spam
    'rate_limit' => 60 // Segundos entre agendamentos do mesmo IP
];

// Define timezone
date_default_timezone_set($config['timezone']);

/**
 * Função para criar diretório de dados se não existir
 */
function criarDiretorioData($diretorio) {
    if (!is_dir($diretorio)) {
        if (!mkdir($diretorio, 0755, true)) {
            throw new Exception('Não foi possível criar diretório de dados');
        }
    }
    
    // Criar arquivo .htaccess para proteger o diretório
    $htaccess = $diretorio . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }
}

/**
 * Função para verificar rate limiting
 */
function verificarRateLimit($config) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $arquivo_rate = $config['diretorio_data'] . '/rate_limit.json';
    
    $rates = [];
    if (file_exists($arquivo_rate)) {
        $rates = json_decode(file_get_contents($arquivo_rate), true) ?? [];
    }
    
    $agora = time();
    $ultimo_acesso = $rates[$ip] ?? 0;
    
    if (($agora - $ultimo_acesso) < $config['rate_limit']) {
        return false;
    }
    
    // Limpar entradas antigas (mais de 1 hora)
    $rates = array_filter($rates, function($timestamp) use ($agora) {
        return ($agora - $timestamp) < 3600;
    });
    
    // Registrar novo acesso
    $rates[$ip] = $agora;
    file_put_contents($arquivo_rate, json_encode($rates));
    
    return true;
}

/**
 * Função para validar dados do agendamento
 */
function validarDados($dados) {
    $erros = [];
    
    // Validar nome
    if (empty($dados['nome']) || strlen(trim($dados['nome'])) < 2) {
        $erros[] = 'Nome deve ter pelo menos 2 caracteres';
    }
    
    if (strlen(trim($dados['nome'])) > 100) {
        $erros[] = 'Nome muito longo (máximo 100 caracteres)';
    }
    
    // Validar telefone
    $telefone = preg_replace('/\D/', '', $dados['telefone'] ?? '');
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $erros[] = 'Telefone deve ter 10 ou 11 dígitos';
    }
    
    // Validar data
    if (empty($dados['data']) || !strtotime($dados['data'])) {
        $erros[] = 'Data inválida';
    } else {
        $dataAgendamento = strtotime($dados['data']);
        $hoje = strtotime('today');
        $limite = strtotime('+30 days');
        
        if ($dataAgendamento < $hoje) {
            $erros[] = 'Data deve ser hoje ou futura';
        }
        
        if ($dataAgendamento > $limite) {
            $erros[] = 'Data deve ser no máximo 30 dias no futuro';
        }
    }
    
    // Validar horário
    $horariosValidos = [
        '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
        '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
        '17:00', '17:30', '18:00', '18:30'
    ];
    
    if (empty($dados['horario']) || !in_array($dados['horario'], $horariosValidos)) {
        $erros[] = 'Horário inválido';
    }
    
    // Validar serviço
    $servicosValidos = [
        'Corte Simples', 'Corte + Barba', 'Barba', 
        'Corte Infantil', 'Corte + Sobrancelha'
    ];
    
    if (empty($dados['servico']) || !in_array($dados['servico'], $servicosValidos)) {
        $erros[] = 'Tipo de serviço inválido';
    }
    
    // Validar observações (opcional)
    if (!empty($dados['observacoes']) && strlen($dados['observacoes']) > 500) {
        $erros[] = 'Observações muito longas (máximo 500 caracteres)';
    }
    
    return $erros;
}

/**
 * Função para verificar se horário já está ocupado
 */
function verificarDisponibilidade($data, $horario, $arquivo) {
    if (!file_exists($arquivo)) {
        return true;
    }
    
    $handle = fopen($arquivo, 'r');
    if (!$handle) {
        return true;
    }
    
    // Pular cabeçalho
    fgetcsv($handle, 1000, ';');
    
    while (($linha = fgetcsv($handle, 1000, ';')) !== FALSE) {
        if (count($linha) >= 5) {
            $dataAgendada = $linha[3] ?? '';
            $horarioAgendado = $linha[4] ?? '';
            $status = $linha[7] ?? '';
            
            // Verificar se é o mesmo dia/horário e não foi cancelado
            if ($dataAgendada === date('d/m/Y', strtotime($data)) && 
                $horarioAgendado === $horario && 
                $status !== 'Cancelado') {
                fclose($handle);
                return false;
            }
        }
    }
    
    fclose($handle);
    return true;
}

/**
 * Função para salvar agendamento no CSV
 */
function salvarAgendamento($dados, $arquivo) {
    // Preparar dados para CSV
    $linha = [
        date('d/m/Y H:i:s'), // Data/hora do agendamento
        trim($dados['nome']),
        trim($dados['telefone']),
        date('d/m/Y', strtotime($dados['data'])),
        $dados['horario'],
        $dados['servico'],
        trim($dados['observacoes'] ?? ''),
        'Pendente', // Status
        'Site Online', // Fonte
        $_SERVER['REMOTE_ADDR'] ?? 'unknown' // IP (para controle)
    ];
    
    // Verificar se arquivo existe, se não, criar com cabeçalho
    if (!file_exists($arquivo)) {
        $cabecalho = [
            'Data/Hora Agendamento',
            'Nome',
            'Telefone',
            'Data Preferida',
            'Horário',
            'Serviço',
            'Observações',
            'Status',
            'Fonte',
            'IP'
        ];
        
        $fp = fopen($arquivo, 'w');
        if ($fp) {
            // Adicionar BOM para UTF-8
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, $cabecalho, ';');
            fclose($fp);
        }
    }
    
    // Adicionar nova linha
    $fp = fopen($arquivo, 'a');
    if ($fp) {
        fputcsv($fp, $linha, ';');
        fclose($fp);
        return true;
    }
    
    return false;
}

/**
 * Função para contar agendamentos
 */
function contarAgendamentos($arquivo) {
    if (!file_exists($arquivo)) {
        return 0;
    }
    
    $linhas = file($arquivo);
    return max(0, count($linhas) - 1); // -1 para descontar o cabeçalho
}

/**
 * Função para enviar resposta JSON
 */
function enviarResposta($sucesso, $mensagem, $dados = null, $codigo = 200) {
    http_response_code($codigo);
    
    $resposta = [
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'timestamp' => date('c'),
        'servidor' => 'Render/Online'
    ];
    
    if ($dados) {
        $resposta['dados'] = $dados;
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit();
}

// Processar apenas requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    enviarResposta(false, 'Método não permitido. Use POST.', null, 405);
}

try {
    // Criar diretório de dados
    criarDiretorioData($config['diretorio_data']);
    
    // Verificar rate limiting
    if (!verificarRateLimit($config)) {
        http_response_code(429);
        enviarResposta(false, 'Muitas tentativas. Aguarde um minuto.', null, 429);
    }
    
    // Verificar limite de agendamentos
    if (contarAgendamentos($config['arquivo_csv']) >= $config['max_agendamentos']) {
        http_response_code(507);
        enviarResposta(false, 'Sistema temporariamente indisponível.', null, 507);
    }
    
    // Obter dados JSON
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    // Verificar se dados foram recebidos
    if (!$dados) {
        http_response_code(400);
        enviarResposta(false, 'Dados inválidos ou não recebidos.', null, 400);
    }
    
    // Log para debug (apenas em desenvolvimento)
    if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
        error_log('Agendamento recebido: ' . print_r($dados, true));
    }
    
    // Validar dados
    $erros = validarDados($dados);
    if (!empty($erros)) {
        http_response_code(400);
        enviarResposta(false, 'Dados inválidos: ' . implode(', ', $erros), null, 400);
    }
    
    // Verificar disponibilidade do horário
    if (!verificarDisponibilidade($dados['data'], $dados['horario'], $config['arquivo_csv'])) {
        http_response_code(409);
        enviarResposta(false, 'Horário já ocupado. Escolha outro horário.', null, 409);
    }
    
    // Salvar agendamento
    if (salvarAgendamento($dados, $config['arquivo_csv'])) {
        // Sucesso
        enviarResposta(true, 'Agendamento salvo com sucesso!', [
            'nome' => $dados['nome'],
            'data' => date('d/m/Y', strtotime($dados['data'])),
            'horario' => $dados['horario'],
            'servico' => $dados['servico']
        ]);
    } else {
        // Erro ao salvar
        http_response_code(500);
        enviarResposta(false, 'Erro interno: não foi possível salvar o agendamento.', null, 500);
    }
    
} catch (Exception $e) {
    // Erro geral
    error_log('Erro no agendamento: ' . $e->getMessage());
    http_response_code(500);
    enviarResposta(false, 'Erro interno do servidor.', null, 500);
}
?>