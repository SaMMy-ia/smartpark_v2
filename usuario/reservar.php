<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/multas_service.php';
requireRole('usuario');

// Verificar se o usuário tem multas pendentes antes de permitir nova reserva
if (usuarioTemMultasPendentes($pdo, $_SESSION['user_id'])) {
    redirectWithMessage('/smartpark/usuario/minhas-multas.php', 'Você possui multas pendentes. Por favor, regularize sua situação antes de realizar uma nova reserva.', 'error');
}

// Verificar multas automáticas ao carregar a página
verificarMultasAutomaticas($pdo);

// Get Parque Rivas info
$parqueId = getParqueRivasId();
$stmt = $pdo->prepare("SELECT * FROM estacionamentos WHERE id = ?");
$stmt->execute([$parqueId]);
$parque = $stmt->fetch();

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vagaId = (int) $_POST['vaga_id'];
    $dataInicio = $_POST['data_inicio'];
    $dataFim = $_POST['data_fim'];
    $veiculoId = !empty($_POST['veiculo_id_sel']) ? (int) $_POST['veiculo_id_sel'] : null;
    $marcaVeiculo = sanitize($_POST['marca_veiculo']);
    $matricula = strtoupper(sanitize($_POST['matricula']));
    $proprietario = sanitize($_POST['proprietario']);
    $contacto = sanitize($_POST['contacto']);

    $errors = [];

    // Validações
    if (empty($marcaVeiculo))
        $errors[] = 'Marca do veículo é obrigatória.';
    if (empty($matricula)) {
        $errors[] = 'Matrícula é obrigatória.';
    } elseif (!validarMatricula($matricula)) {
        $errors[] = 'Formato de matrícula inválido. Use formato: ABC-1234';
    }
    if (empty($proprietario))
        $errors[] = 'Nome do proprietário é obrigatório.';
    if (empty($contacto)) {
        $errors[] = 'Contacto é obrigatório.';
    } elseif (!validarContacto($contacto)) {
        $errors[] = 'Contacto inválido. Deve ter pelo menos 9 dígitos.';
    }

    // Verificar sobreposição de horários
    if (verificarSobreposicaoHorario($pdo, $vagaId, $dataInicio, $dataFim)) {
        $errors[] = 'Esta vaga já está reservada para o horário selecionado. Por favor, escolha outro horário.';
    }

    if (empty($errors)) {
        // Calculate hours and total value
        $hours = calculateHours($dataInicio, $dataFim);
        $valorTotal = $hours * $parque['preco_hora'];

        // Create reservation
        $stmt = $pdo->prepare("
            INSERT INTO reservas (
                usuario_id, vaga_id, veiculo_id, data_inicio, data_fim, status, valor_total,
                marca_veiculo, matricula, proprietario, contacto
            ) VALUES (?, ?, ?, ?, ?, 'ativa', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $vagaId,
            $veiculoId,
            $dataInicio,
            $dataFim,
            $valorTotal,
            $marcaVeiculo,
            $matricula,
            $proprietario,
            $contacto
        ]);
        $reservaId = $pdo->lastInsertId();

        // Create payment
        $stmt = $pdo->prepare("INSERT INTO pagamentos (reserva_id, metodo, status, valor) VALUES (?, 'cartao', 'pendente', ?)");
        $stmt->execute([$reservaId, $valorTotal]);

        // Get vaga info for log
        $stmt = $pdo->prepare("SELECT numero_vaga FROM vagas WHERE id = ?");
        $stmt->execute([$vagaId]);
        $vaga = $stmt->fetch();

        logAction($_SESSION['user_id'], "Reserva criada - {$vaga['numero_vaga']} - Matrícula: $matricula");

        redirectWithMessage('/smartpark/usuario/pagamento.php?reserva=' . $reservaId, 'Reserva criada com sucesso! Prossiga para o pagamento.');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-parking mr-2"></i> Reservar Vaga - <?php echo e($parque['nome']); ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?php echo e($parque['endereco']); ?> • <?php echo formatCurrency($parque['preco_hora']); ?>/hora
        </p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle mr-2 mt-1"></i>
                <div>
                    <p class="font-bold">Erros encontrados:</p>
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="reservaForm" class="space-y-6">
        <!-- Step 1: Select Time -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary dark:bg-secondary text-white mr-2">1</span>
                Selecione o Horário
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-alt mr-1"></i> Data e Hora de Início
                    </label>
                    <input type="datetime-local" name="data_inicio" id="data_inicio" required
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-check mr-1"></i> Data e Hora de Término
                    </label>
                    <input type="datetime-local" name="data_fim" id="data_fim" required
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
            </div>
        </div>

        <!-- Step 2: Select Parking Spot -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary dark:bg-secondary text-white mr-2">2</span>
                Escolha a Vaga
            </h2>

            <div class="mb-4 flex items-center gap-4 text-sm">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                    <span class="text-gray-700 dark:text-gray-300">Livre</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
                    <span class="text-gray-700 dark:text-gray-300">Reservada</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                    <span class="text-gray-700 dark:text-gray-300">Ocupada/Multa</span>
                </div>
            </div>

            <div id="vagasGallery" class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                <!-- Vagas will be loaded here via JavaScript -->
            </div>

            <input type="hidden" name="vaga_id" id="vaga_id" required>
            <div id="vagaSelecionada" class="mt-4 hidden">
                <p class="text-sm text-gray-600 dark:text-gray-400">Vaga selecionada:</p>
                <p class="text-lg font-bold text-primary dark:text-secondary" id="vagaNome"></p>
            </div>
        </div>

        <!-- Step 3: Vehicle Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary dark:bg-secondary text-white mr-2">3</span>
                Informações do Veículo
            </h2>

            <?php
            // Get user's vehicles for selection
            $stmtVeh = $pdo->prepare("SELECT * FROM veiculos WHERE usuario_id = ?");
            $stmtVeh->execute([$_SESSION['user_id']]);
            $userVehicles = $stmtVeh->fetchAll();
            ?>

            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    <i class="fas fa-list mr-1"></i> Selecione um dos seus veículos (Matrícula)
                </label>
               
            </div>
            <select id="vehicleSelect" name="veiculo_id_sel"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                <?php if (empty($userVehicles)): ?>
                    <option value="">-- Nenhum veículo cadastrado --</option>
                <?php else: ?>
                    <?php foreach ($userVehicles as $index => $veh): ?>
                        <option value="<?php echo $veh['id']; ?>" data-marca="<?php echo e($veh['marca']); ?>"
                            data-modelo="<?php echo e($veh['modelo']); ?>" data-matricula="<?php echo e($veh['matricula']); ?>"
                            data-proprietario="<?php echo e($veh['proprietario_nome']); ?>"
                            data-contacto="<?php echo e($veh['contacto']); ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                            <?php echo e($veh['matricula']); ?> (<?php echo e($veh['marca']); ?>)
                        </option>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
            </select>
            <p class="text-xs text-gray-500 mt-1">Ao selecionar um veículo, os dados serão preenchidos automaticamente e
                protegidos contra edição.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-car mr-1"></i> Marca do Veículo *
                    </label>
                    <input type="text" name="marca_veiculo" id="marca_veiculo" required
                        placeholder="Ex: Toyota, BMW, Ford" value="<?php echo e($_POST['marca_veiculo'] ?? ''); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-id-card mr-1"></i> Matrícula (Placa) *
                    </label>
                    <input type="text" name="matricula" id="matricula" required
                        placeholder="ABC-123-DE (Formato Moçambicano)" maxlength="11"
                        pattern="[A-Z]{3}-[0-9]{3}-[A-Z]{2}" title="Formato: ABC-123-DE (3 letras, 3 números, 2 letras)"
                        value="<?php echo e($_POST['matricula'] ?? ''); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary uppercase">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Formato obrigatório: ABC-123-DE (3 letras + 3 números + 2 letras)
                    </p>
                    <p id="matriculaError" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden">
                        Matrícula inválida! Use o formato: ABC-123-DE
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user mr-1"></i> Proprietário *
                    </label>
                    <input type="text" name="proprietario" id="proprietario" required
                        placeholder="Nome completo do proprietário"
                        value="<?php echo e($_POST['proprietario'] ?? $currentUser['nome']); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-phone mr-1"></i> Contacto (Telefone) *
                    </label>
                    <input type="tel" name="contacto" id="contacto" required placeholder="Ex: 912345678"
                        value="<?php echo e($_POST['contacto'] ?? ''); ?>"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
            </div>
        </div>

        <!-- Summary and Submit -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Resumo da Reserva</h2>

            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-700 dark:text-gray-300">Duração estimada:</span>
                    <span class="font-semibold text-gray-900 dark:text-white" id="horasEstimadas">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Valor total:</span>
                    <span class="text-2xl font-bold text-primary dark:text-secondary" id="valorEstimado">0,00 MT</span>
                </div>
            </div>

            <button type="submit"
                class="w-full px-6 py-3 bg-primary dark:bg-secondary text-white rounded-lg font-semibold hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                id="submitBtn" disabled>
                <i class="fas fa-check mr-2"></i> Confirmar Reserva e Prosseguir para Pagamento
            </button>
        </div>
    </form>
</div>

<script>
    const precoHora = <?php echo $parque['preco_hora']; ?>;
    let vagaSelecionadaId = null;

    // Load parking spots
    async function carregarVagas() {
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;

        if (!dataInicio || !dataFim) {
            return;
        }

        try {
            const response = await fetch(`/smartpark/includes/api_vagas.php?action=listar_vagas&data_inicio=${encodeURIComponent(dataInicio)}&data_fim=${encodeURIComponent(dataFim)}`);
            const data = await response.json();

            const gallery = document.getElementById('vagasGallery');
            gallery.innerHTML = '';

            data.vagas.forEach(vaga => {
                const vagaEl = document.createElement('button');
                vagaEl.type = 'button';
                vagaEl.className = 'aspect-square rounded-lg font-semibold text-sm transition transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary';

                // Determine color based on availability
                if (vaga.disponivel) {
                    vagaEl.className += ' bg-green-500 hover:bg-green-600 text-white';
                } else {
                    vagaEl.className += ' bg-red-500 text-white cursor-not-allowed opacity-75';
                    vagaEl.disabled = true;
                }

                // Extract number from "Vaga X"
                const numero = vaga.numero_vaga.replace('Vaga ', '');
                vagaEl.textContent = numero;
                vagaEl.title = vaga.numero_vaga + ' - ' + (vaga.disponivel ? 'Disponível' : 'Ocupada');

                if (vaga.disponivel) {
                    vagaEl.onclick = () => selecionarVaga(vaga.id, vaga.numero_vaga);
                }

                // Highlight if selected
                if (vagaSelecionadaId === vaga.id) {
                    vagaEl.className += ' ring-4 ring-blue-500';
                }

                gallery.appendChild(vagaEl);
            });
        } catch (error) {
            console.error('Erro ao carregar vagas:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar vagas. Por favor, tente novamente.',
                confirmButtonColor: '#1E3A8A'
            });
        }
    }

    function selecionarVaga(vagaId, numeroVaga) {
        vagaSelecionadaId = vagaId;
        document.getElementById('vaga_id').value = vagaId;
        document.getElementById('vagaNome').textContent = numeroVaga;
        document.getElementById('vagaSelecionada').classList.remove('hidden');

        // Reload gallery to highlight selection
        carregarVagas();

        // Enable submit button if all required fields are filled
        verificarFormulario();
    }

    function calcularValor() {
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;

        if (dataInicio && dataFim) {
            const inicio = new Date(dataInicio);
            const fim = new Date(dataFim);

            if (fim > inicio) {
                const diffMs = fim - inicio;
                const diffHours = diffMs / (1000 * 60 * 60);
                const valor = diffHours * precoHora;

                document.getElementById('horasEstimadas').textContent = diffHours.toFixed(1) + ' horas';
                document.getElementById('valorEstimado').textContent = valor.toFixed(2).replace('.', ',') + ' MT';

                // Reload vagas with new time range
                carregarVagas();
            }
        }
    }

    // Handle vehicle change
    document.getElementById('vehicleSelect').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const isNew = this.value === 'new' || this.value === '';

        if (!isNew) {
            document.getElementById('marca_veiculo').value = selectedOption.getAttribute('data-marca');
            document.getElementById('matricula').value = selectedOption.getAttribute('data-matricula');

            // Fix: Pull owner name, fallback to current user if empty
            const owner = selectedOption.getAttribute('data-proprietario');
            document.getElementById('proprietario').value = (owner && owner !== 'null') ? owner : '<?php echo e($currentUser['nome']); ?>';

            document.getElementById('contacto').value = selectedOption.getAttribute('data-contacto') || '';

            // Set fields to readonly Except Contact
            document.getElementById('marca_veiculo').readOnly = true;
            document.getElementById('matricula').readOnly = true;
            document.getElementById('proprietario').readOnly = true;
            document.getElementById('contacto').readOnly = false; // explicitly allowed to edit

            // Visual feedback for readonly
            document.getElementById('marca_veiculo').classList.add('bg-gray-100', 'dark:bg-gray-600');
            document.getElementById('matricula').classList.add('bg-gray-100', 'dark:bg-gray-600');
            document.getElementById('proprietario').classList.add('bg-gray-100', 'dark:bg-gray-600');
            document.getElementById('contacto').classList.remove('bg-gray-100', 'dark:bg-gray-600');
        } else {
            // Clear fields and allow editing
            document.getElementById('marca_veiculo').value = '';
            document.getElementById('matricula').value = '';
            document.getElementById('proprietario').value = '<?php echo e($currentUser['nome']); ?>';
            document.getElementById('contacto').value = '';

            document.getElementById('marca_veiculo').readOnly = false;
            document.getElementById('matricula').readOnly = false;
            document.getElementById('proprietario').readOnly = false;

            document.getElementById('marca_veiculo').classList.remove('bg-gray-100', 'dark:bg-gray-600');
            document.getElementById('matricula').classList.remove('bg-gray-100', 'dark:bg-gray-600');
            document.getElementById('proprietario').classList.remove('bg-gray-100', 'dark:bg-gray-600');
        }

        // Trigger verification
        verificarFormulario();
    });

    // Auto-select first vehicle on load
    window.addEventListener('DOMContentLoaded', () => {
        const vehicleSelect = document.getElementById('vehicleSelect');
        if (vehicleSelect && vehicleSelect.value && vehicleSelect.value !== 'new') {
            vehicleSelect.dispatchEvent(new Event('change'));
        }
    });

    function verificarFormulario() {
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;
        const vagaId = document.getElementById('vaga_id').value;
        const marcaVeiculo = document.getElementById('marca_veiculo').value;
        const matricula = document.getElementById('matricula').value;
        const proprietario = document.getElementById('proprietario').value;
        const contacto = document.getElementById('contacto').value;

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = !(dataInicio && dataFim && vagaId && marcaVeiculo && matricula && proprietario && contacto);
    }

    // Event listeners
    document.getElementById('data_inicio').addEventListener('change', calcularValor);
    document.getElementById('data_fim').addEventListener('change', calcularValor);
    document.getElementById('marca_veiculo').addEventListener('input', verificarFormulario);
    document.getElementById('matricula').addEventListener('input', verificarFormulario);
    document.getElementById('proprietario').addEventListener('input', verificarFormulario);
    document.getElementById('contacto').addEventListener('input', verificarFormulario);

    // Auto-uppercase and format matricula
    document.getElementById('matricula').addEventListener('input', function (e) {
        let value = e.target.value.toUpperCase();

        // Remove caracteres inválidos
        value = value.replace(/[^A-Z0-9-]/g, '');

        // Auto-adicionar hífens no formato ABC-123-DE
        if (value.length > 3 && value[3] !== '-') {
            value = value.slice(0, 3) + '-' + value.slice(3);
        }
        if (value.length > 7 && value[7] !== '-') {
            value = value.slice(0, 7) + '-' + value.slice(7);
        }

        e.target.value = value;

        // Validar formato moçambicano: ABC-123-DE
        const pattern = /^[A-Z]{3}-[0-9]{3}-[A-Z]{2}$/;
        const errorMsg = document.getElementById('matriculaError');

        if (value.length > 0 && !pattern.test(value) && value.length >= 11) {
            errorMsg.classList.remove('hidden');
            e.target.classList.add('border-red-500');
        } else {
            errorMsg.classList.add('hidden');
            e.target.classList.remove('border-red-500');
        }
    });

    // Form validation
    document.getElementById('reservaForm').addEventListener('submit', function (e) {
        const dataInicio = new Date(document.getElementById('data_inicio').value);
        const dataFim = new Date(document.getElementById('data_fim').value);
        const matricula = document.getElementById('matricula').value;

        if (dataFim <= dataInicio) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'A data de término deve ser posterior à data de início.',
                confirmButtonColor: '#1E3A8A'
            });
            return false;
        }

        if (!vagaSelecionadaId) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Seleção Pendente',
                text: 'Por favor, selecione uma vaga.',
                confirmButtonColor: '#1E3A8A'
            });
            return false;
        }

        // Validar formato de matrícula moçambicana
        const pattern = /^[A-Z]{3}-[0-9]{3}-[A-Z]{2}$/;
        if (!pattern.test(matricula)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Matrícula Inválida',
                text: 'Matrícula inválida! Use o formato moçambicano: ABC-123-DE (3 letras + 3 números + 2 letras)',
                confirmButtonColor: '#1E3A8A'
            });
            document.getElementById('matricula').focus();
            return false;
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>