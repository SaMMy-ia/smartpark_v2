    </main>
    
    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-car text-primary dark:text-secondary mr-2"></i>
                        SmartPark
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Sistema inteligente de gerenciamento de estacionamentos. 
                        Encontre, reserve e pague por vagas de forma rápida e segura.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Links Rápidos</h3>
                    <ul class="space-y-2 text-sm">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo getRoleRedirect($_SESSION['user_role']); ?>" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-secondary transition">Dashboard</a></li>
                            <?php if (hasRole('usuario')): ?>
                            <li><a href="/smartpark/usuario/buscar-vagas.php" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-secondary transition">Buscar Vagas</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="/smartpark/index.php" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-secondary transition">Login</a></li>
                            <li><a href="/smartpark/register.php" class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-secondary transition">Registrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contato</h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i> contato@smartpark.com</li>
                        <li><i class="fas fa-phone mr-2"></i> (+258) 84 111 1111</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Maputo, Maputo</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> SmartPark. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Custom JavaScript -->
    <script src="/smartpark/assets/js/main.js"></script>
</body>
</html>
