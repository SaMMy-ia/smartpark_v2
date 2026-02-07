<?php
/**
 * SmartPark - Utility Functions
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output for HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return trim(strip_tags($input));
}

/**
 * Format currency (MZN - Mozambican Metical)
 */
function formatCurrency($value) {
    return number_format($value, 2, ',', '.') . ' MT';
}

/**
 * Format date/time
 */
function formatDateTime($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

/**
 * Format date only
 */
function formatDate($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y');
}

/**
 * Calculate hours between two datetimes
 */
function calculateHours($start, $end) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $interval = $startDate->diff($endDate);
    
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    
    return $hours + ($minutes / 60);
}

/**
 * Get status badge class (includes fine-related statuses)
 */
function getStatusBadge($status) {
    $badges = [
        'ativa' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'cancelada' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'concluida' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'em_multa' => 'bg-red-600 text-white dark:bg-red-700 dark:text-white',
        'pendente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'pago' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'falha' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'livre' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'ocupada' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'ocupada_multa' => 'bg-red-600 text-white dark:bg-red-700 dark:text-white',
        'reservada' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    ];
    
    return $badges[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Get tipo vaga badge class
 */
function getTipoBadge($tipo) {
    $badges = [
        'normal' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'deficiente' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        'eletrico' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    ];
    
    return $badges[$tipo] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Pagination helper
 */
function paginate($total, $perPage = 10, $currentPage = 1) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format
 */
function isValidDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate and format matricula (license plate)
 * Mozambican format only: ABC-123-DE
 * 3 letters + hyphen + 3 numbers + hyphen + 2 letters
 */
function validarMatricula($matricula) {
    // Remove espaços e converte para maiúsculas
    $matricula = strtoupper(trim($matricula));
    
    // Formato moçambicano obrigatório: ABC-123-DE
    // 3 letras maiúsculas + hífen + 3 números + hífen + 2 letras maiúsculas
    $pattern = '/^[A-Z]{3}-[0-9]{3}-[A-Z]{2}$/';
    
    return preg_match($pattern, $matricula) === 1;
}

/**
 * Format matricula for display
 * Mozambican format: ABC-123-DE (already formatted)
 */
function formatarMatricula($matricula) {
    $matricula = strtoupper(trim($matricula));
    
    // Se já está no formato correto, retorna como está
    if (preg_match('/^[A-Z]{3}-[0-9]{3}-[A-Z]{2}$/', $matricula)) {
        return $matricula;
    }
    
    // Remove hífens existentes para reformatar
    $matricula = str_replace('-', '', $matricula);
    
    // Reformata para ABC-123-DE
    if (strlen($matricula) === 8) {
        return substr($matricula, 0, 3) . '-' . substr($matricula, 3, 3) . '-' . substr($matricula, 6, 2);
    }
    
    return $matricula;
}

/**
 * Check if a time slot overlaps with existing reservations
 * Returns true if there's an overlap (conflict), false if it's available
 */
function verificarSobreposicaoHorario($pdo, $vagaId, $dataInicio, $dataFim, $reservaIdExcluir = null) {
    $sql = "SELECT COUNT(*) as count FROM reservas 
            WHERE vaga_id = ? 
            AND status IN ('ativa', 'em_multa')
            AND (
                (data_inicio < ? AND data_fim > ?) OR
                (data_inicio < ? AND data_fim > ?) OR
                (data_inicio >= ? AND data_fim <= ?)
            )";
    
    $params = [$vagaId, $dataFim, $dataInicio, $dataFim, $dataInicio, $dataInicio, $dataFim];
    
    if ($reservaIdExcluir) {
        $sql .= " AND id != ?";
        $params[] = $reservaIdExcluir;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
}

/**
 * Get current status of a parking spot
 * Considers active reservations and their time slots
 */
function getStatusVaga($pdo, $vagaId, $dataHora = null) {
    if (!$dataHora) {
        $dataHora = date('Y-m-d H:i:s');
    }
    
    // Check if there's an active reservation at this time
    $stmt = $pdo->prepare("
        SELECT r.status, r.data_fim
        FROM reservas r
        WHERE r.vaga_id = ?
        AND r.status IN ('ativa', 'em_multa')
        AND r.data_inicio <= ?
        AND r.data_fim >= ?
        LIMIT 1
    ");
    $stmt->execute([$vagaId, $dataHora, $dataHora]);
    $reserva = $stmt->fetch();
    
    if ($reserva) {
        if ($reserva['status'] === 'em_multa') {
            return 'ocupada_multa';
        }
        return 'ocupada';
    }
    
    // Check if there's a future reservation
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM reservas
        WHERE vaga_id = ?
        AND status = 'ativa'
        AND data_inicio > ?
        LIMIT 1
    ");
    $stmt->execute([$vagaId, $dataHora]);
    $futuraReserva = $stmt->fetch();
    
    if ($futuraReserva['count'] > 0) {
        return 'reservada';
    }
    
    return 'livre';
}

/**
 * Get detailed status of all parking spots for a given time range
 */
function getStatusTodasVagas($pdo, $dataInicio = null, $dataFim = null) {
    if (!$dataInicio) {
        $dataInicio = date('Y-m-d H:i:s');
    }
    if (!$dataFim) {
        $dataFim = date('Y-m-d H:i:s', strtotime('+2 hours'));
    }
    
    $stmt = $pdo->query("
        SELECT v.id, v.numero_vaga, v.tipo, v.status as status_base
        FROM vagas v
        WHERE v.estacionamento_id = 1
        ORDER BY v.id
    ");
    $vagas = $stmt->fetchAll();
    
    $resultado = [];
    foreach ($vagas as $vaga) {
        $temConflito = verificarSobreposicaoHorario($pdo, $vaga['id'], $dataInicio, $dataFim);
        
        $resultado[] = [
            'id' => $vaga['id'],
            'numero_vaga' => $vaga['numero_vaga'],
            'tipo' => $vaga['tipo'],
            'disponivel' => !$temConflito,
            'status' => $temConflito ? 'ocupada' : 'livre'
        ];
    }
    
    return $resultado;
}

/**
 * Get Parque Rivas ID (always 1)
 */
function getParqueRivasId() {
    return 1;
}

/**
 * Validate phone/contact number
 */
function validarContacto($contacto) {
    // Remove non-numeric characters
    $contacto = preg_replace('/[^0-9]/', '', $contacto);
    
    // Must have at least 9 digits (mobile without country code)
    return strlen($contacto) >= 9;
}


