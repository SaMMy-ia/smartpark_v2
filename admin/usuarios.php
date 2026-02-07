<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Don't allow deleting yourself
    if ($id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        if ($stmt->execute([$id])) {
            logAction($_SESSION['user_id'], "Usuário ID $id deletado");
            redirectWithMessage('/smartpark/admin/usuarios.php', 'Usuário deletado com sucesso!');
        }
    } else {
        redirectWithMessage('/smartpark/admin/usuarios.php', 'Você não pode deletar sua própria conta!', 'error');
    }
}

// Handle CREATE/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $role = $_POST['role'];
    $senha = $_POST['senha'] ?? '';
    
    // Validate email
    if (!isValidEmail($email)) {
        redirectWithMessage('smartparkPark/admin/usuarios.php', 'Email inválido!', 'error');
    }
    
    // Check if email exists (except for current user being edited)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id ?? 0]);
    if ($stmt->fetch()) {
        redirectWithMessage('/smartpark/admin/usuarios.php', 'Este email já está em uso!', 'error');
    }
    
    if ($id) {
        // UPDATE
        if (!empty($senha)) {
            $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, role = ?, senha = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $role, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $role, $id]);
        }
        logAction($_SESSION['user_id'], "Usuário ID $id atualizado");
        redirectWithMessage('/smartpark/admin/usuarios.php', 'Usuário atualizado com sucesso!');
    } else {
        // CREATE
        if (empty($senha)) {
            redirectWithMessage('/smartpark/admin/usuarios.php', 'Senha é obrigatória para novos usuários!', 'error');
        }
        $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $hashedPassword, $role]);
        logAction($_SESSION['user_id'], "Usuário criado: $nome ($role)");
        redirectWithMessage('/smartpark/admin/usuarios.php', 'Usuário criado com sucesso!');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$totalUsuarios = $stmt->fetch()['total'];
$totalPages = ceil($totalUsuarios / $perPage);

// Get usuarios
$stmt = $pdo->prepare("SELECT * FROM usuarios ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$usuarios = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-users mr-2"></i> Gerenciar Usuários
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Adicione, edite ou remova usuários do sistema</p>
        </div>
        <button onclick="openModal()" class="bg-primary dark:bg-secondary text-white px-6 py-3 rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition shadow-lg">
            <i class="fas fa-user-plus mr-2"></i> Novo Usuário
        </button>
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <?php
        $stmt = $pdo->query("SELECT role, COUNT(*) as total FROM usuarios GROUP BY role");
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $totalUsuarios; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Admins</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo $stats['admin'] ?? 0; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Funcionários</p>
            <p class="text-2xl font-bold text-green-600"><?php echo $stats['funcionario'] ?? 0; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Usuários</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo $stats['usuario'] ?? 0; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Contabilistas</p>
            <p class="text-2xl font-bold text-red-600"><?php echo ($stats['contabilista_estagiario'] ?? 0) + ($stats['contabilista_senior'] ?? 0); ?></p>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" id="searchInput" onkeyup="filterTable('searchInput', 'usuariosTable')"
                   placeholder="Buscar usuários..." 
                   class="w-full md:w-96 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="usuariosTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data Criação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($usuarios as $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">#<?php echo $user['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($user['nome']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo e($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $roleBadges = [
                                'admin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'funcionario' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'usuario' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                'contabilista_estagiario' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                'contabilista_senior' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $roleBadges[$user['role']]; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo formatDate($user['data_criacao']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick='editUsuario(<?php echo json_encode($user); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" onclick="return confirmDelete('Tem certeza que deseja deletar este usuário?')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando <span class="font-medium"><?php echo $offset + 1; ?></span> a <span class="font-medium"><?php echo min($offset + $perPage, $totalUsuarios); ?></span> de <span class="font-medium"><?php echo $totalUsuarios; ?></span> resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'; ?> relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div id="usuarioModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modalTitle">Novo Usuário</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="id" id="usuario_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome Completo</label>
                    <input type="text" name="nome" id="nome" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" id="email" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
                    <select name="role" id="role" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                        <option value="usuario">Usuário</option>
                        <option value="funcionario">Funcionário</option>
                        <option value="admin">Admin</option>
                        <option value="contabilista_estagiario">Contabilista Estagiário</option>
                        <option value="contabilista_senior">Contabilista Sénior</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Senha <span id="senhaOpcional" class="text-xs text-gray-500">(deixe em branco para manter)</span></label>
                    <input type="password" name="senha" id="senha" minlength="6" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-primary dark:bg-secondary text-white rounded-lg hover:bg-blue-900 dark:hover:bg-green-600 transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('usuarioModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Novo Usuário';
    document.getElementById('usuario_id').value = '';
    document.getElementById('nome').value = '';
    document.getElementById('email').value = '';
    document.getElementById('role').value = 'usuario';
    document.getElementById('senha').value = '';
    document.getElementById('senha').required = true;
    document.getElementById('senhaOpcional').classList.add('hidden');
}

function closeModal() {
    document.getElementById('usuarioModal').classList.add('hidden');
}

function editUsuario(user) {
    document.getElementById('usuarioModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Editar Usuário';
    document.getElementById('usuario_id').value = user.id;
    document.getElementById('nome').value = user.nome;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('senha').value = '';
    document.getElementById('senha').required = false;
    document.getElementById('senhaOpcional').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
