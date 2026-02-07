<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('usuario');

$reservaId = isset($_GET['reserva']) ? (int)$_GET['reserva'] : null;
$somenteMultas = isset($_GET['multa']) && $_GET['multa'] == 1;

// Se não tiver reserva nem pagamento de multa, redireciona
if (!$reservaId && !$somenteMultas) {
    redirectWithMessage('/SmartPark/usuario/minhas-reservas.php', 'Reserva não encontrada.', 'error');
}

// Pegar dados da reserva (se houver)
$reserva = null;
if ($reservaId) {
    $stmt = $pdo->prepare("
        SELECT r.*, v.numero_vaga, e.nome as estacionamento_nome, e.endereco
        FROM reservas r
        JOIN vagas v ON r.vaga_id = v.id
        JOIN estacionamentos e ON v.estacionamento_id = e.id
        WHERE r.id = ? AND r.usuario_id = ?
    ");
    $stmt->execute([$reservaId, $_SESSION['user_id']]);
    $reserva = $stmt->fetch();

    if (!$reserva && !$somenteMultas) {
        redirectWithMessage('/SmartPark/usuario/minhas-reservas.php', 'Reserva não encontrada.', 'error');
    }
}

// Valor total da reserva pendente
$valorReservaPendente = 0;
if (!$somenteMultas && $reserva) {
    // Verifica se já existe pagamento da reserva
    $stmt = $pdo->prepare("
        SELECT status 
        FROM pagamentos 
        WHERE reserva_id = ? 
        ORDER BY data_pagamento DESC 
        LIMIT 1
    ");
    $stmt->execute([$reservaId]);
    $pagamentoExistente = $stmt->fetch();
    
    if (!$pagamentoExistente || $pagamentoExistente['status'] !== 'pago') {
        $valorReservaPendente = $reserva['valor_total'];
    }
}

// Somar todas as multas pendentes do usuário
$stmt = $pdo->prepare("
    SELECT SUM(valor_multa) as total_multas
    FROM reservas
    WHERE usuario_id = ? AND status = 'em_multa'
");
$stmt->execute([$_SESSION['user_id']]);
$totalMultas = $stmt->fetch()['total_multas'] ?? 0;

// Total a pagar
$valorPagar = $valorReservaPendente + $totalMultas;

// Criar descrição detalhada
$descricao = [];
if ($valorReservaPendente > 0) {
    $descricao[] = "Reserva: " . formatCurrency($valorReservaPendente);
}
if ($totalMultas > 0) {
    $descricao[] = "Multas pendentes: " . formatCurrency($totalMultas);
}
$descricaoCompleta = implode(' + ', $descricao);

// Processar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo'];

    // Pagar reserva pendente
    if ($valorReservaPendente > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pagamentos (reserva_id, metodo, status, valor, descricao, data_pagamento)
            VALUES (?, ?, 'pago', ?, ?, NOW())
        ");
        $stmt->execute([$reservaId, $metodo, $valorReservaPendente, "Pagamento da reserva"]);
        
        // Atualizar status da reserva
        $stmt = $pdo->prepare("UPDATE reservas SET status = 'ativa' WHERE id = ?");
        $stmt->execute([$reservaId]);
    }

    // Pagar todas as multas
    if ($totalMultas > 0) {
        $stmt = $pdo->prepare("
            UPDATE reservas
            SET status = 'concluida', valor_multa = 0
            WHERE usuario_id = ? AND status = 'em_multa'
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }

    // Registrar pagamento geral (reserva + multas)
    $reservaParaPagamento = $somenteMultas ? 0 : $reservaId;
    $stmt = $pdo->prepare("
        INSERT INTO pagamentos (reserva_id, metodo, status, valor, descricao, data_pagamento)
        VALUES (?, ?, 'pago', ?, ?, NOW())
    ");
    $stmt->execute([$reservaParaPagamento, $metodo, $valorPagar, $descricaoCompleta]);

    logAction($_SESSION['user_id'], "Pagamento realizado: $descricaoCompleta via $metodo");

    redirectWithMessage('/SmartPark/usuario/minhas-reservas.php', 'Pagamento realizado com sucesso!');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/SmartPark/usuario/minhas-reservas.php" class="text-primary dark:text-secondary hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para reservas
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-4">
            <i class="fas fa-credit-card mr-2"></i> Pagamento
        </h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Resumo do Recibo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Resumo do Pagamento</h3>
            <?php if (!empty($descricaoCompleta)): ?>
                <p class="text-gray-700 dark:text-gray-300"><?php echo $descricaoCompleta; ?></p>
                <p class="mt-2 font-bold text-3xl text-primary dark:text-secondary"><?php echo formatCurrency($valorPagar); ?></p>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400">Nenhum pagamento pendente.</p>
            <?php endif; ?>
        </div>

        <!-- Formulário de Pagamento -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Método de Pagamento</h3>

            <?php if ($valorPagar <= 0): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold">Não há pagamentos pendentes!</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="" id="paymentForm">
                    <div class="space-y-4">
                        <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary dark:hover:border-secondary transition">
                            <input type="radio" name="metodo" value="cartao" required class="mr-3">
                            <i class="fas fa-credit-card text-2xl text-primary dark:text-secondary mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Cartão de Crédito/Débito</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Pagamento instantâneo</p>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary dark:hover:border-secondary transition">
                            <input type="radio" name="metodo" value="pix" required class="mr-3">
                            <i class="fas fa-qrcode text-2xl text-primary dark:text-secondary mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">PIX</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Transferência instantânea</p>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-primary dark:hover:border-secondary transition">
                            <input type="radio" name="metodo" value="boleto" required class="mr-3">
                            <i class="fas fa-barcode text-2xl text-primary dark:text-secondary mr-3"></i>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Boleto Bancário</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Vencimento em 3 dias úteis</p>
                            </div>
                        </label>

                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <i class="fas fa-info-circle mr-1"></i>
                                Este é um pagamento simulado. Nenhuma cobrança real será efetuada.
                            </p>
                        </div>

                        <button type="submit" class="w-full px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg font-semibold hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
                            <i class="fas fa-check mr-2"></i> Confirmar Pagamento
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
