<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas.php';
requireRole('funcionario');

// Verificar multas automáticas
verificarMultasAutomaticas($pdo);

// OCULTAÇÃO DE DADOS: Dados antigos não são mais excluídos.
// O código abaixo foi comentado para preservar o histórico no banco de dados.
/*
$pdo->exec("
    DELETE FROM reservas
    WHERE status = 'finalizada'
      AND data_fim < DATE_SUB(NOW(), INTERVAL 2 WEEK)
");
*/

// Buscar todas reservas ordenadas por status e data
$stmt = $pdo->query("
    SELECT r.*, u.nome as usuario_nome, v.numero_vaga, v.id as vaga_id,
           e.nome as estacionamento_nome
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN vagas v ON r.vaga_id = v.id
    JOIN estacionamentos e ON v.estacionamento_id = e.id
    WHERE r.status NOT IN ('finalizada', 'vaga_liberada', 'concluida', 'cancelada') 
       OR r.data_fim >= DATE_SUB(NOW(), INTERVAL 2 WEEK)
    ORDER BY 
        CASE 
            WHEN r.status = 'ativa' THEN 1
            WHEN r.status = 'em_multa' THEN 2
            WHEN r.status = 'finalizada' THEN 3
            WHEN r.status = 'vaga_liberada' THEN 4
            ELSE 5
        END,
        r.data_inicio DESC
");
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-calendar-check mr-2"></i> Visualizar Reservas
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Consulte e gerencie todas as reservas do parque</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" id="searchInput"
                   onkeyup="filterTable('searchInput', 'reservasTable')"
                   placeholder="Buscar por matrícula, proprietário, vaga..."
                   class="w-full md:w-96 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="reservasTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th>ID</th>
                        <th>Vaga</th>
                        <th>Veículo</th>
                        <th>Proprietário</th>
                        <th>Contacto</th>
                        <th>Período</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($reservas as $r): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo e($r['numero_vaga']); ?></td>
                            <td>
                                <p class="font-semibold"><?php echo e($r['marca_veiculo'] ?? '-'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e($r['matricula'] ?? '-'); ?></p>
                            </td>
                            <td><?php echo e($r['proprietario'] ?? $r['usuario_nome']); ?></td>
                            <td><?php echo e($r['contacto'] ?? '-'); ?></td>
                            <td>
                                <?php echo formatDateTime($r['data_inicio']); ?><br>
                                <span class="text-xs text-gray-500">até <?php echo formatDateTime($r['data_fim']); ?></span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadge($r['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $r['status'])); ?>
                                </span>
                                <?php if (in_array($r['status'], ['em_multa', 'vaga_liberada'])): ?>
                                    <p class="text-xs text-red-600 mt-1">
                                        Multa: <?php echo formatCurrency($r['valor_multa'] ?? 0); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($r['valor_total'] ?? 0); ?></td>
                            <td>
                                <?php if (in_array($r['status'], ['ativa', 'em_multa'])): ?>
                                    <a href="liberar_reserva.php?id=<?php echo $r['id']; ?>"
                                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold btn-liberar-vaga">
                                        Liberar vaga
                                    </a>
                                <?php elseif ($r['status'] === 'vaga_liberada'): ?>
                                    <span class="text-gray-500 text-sm">Vaga liberada</span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Finalizada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-liberar-vaga').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        Swal.fire({
            title: 'Liberar Vaga',
            text: 'Confirmar saída do veículo e liberar vaga?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sim, liberar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});

function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId).value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < td.length; j++) {
            if (td[j] && td[j].textContent.toUpperCase().includes(input)) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? '' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
