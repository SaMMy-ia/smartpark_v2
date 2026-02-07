// SmartPark PWA Installation Manager
// Gerencia prompt de instalação e experiência do usuário

class PWAInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = null;
        this.isInstalled = false;
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        
        this.init();
    }
    
    init() {
        // Verifica se já está instalado
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            this.isInstalled = true;
            console.log('[PWA] App já está instalado');
            return;
        }
        
        // Listener para o evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA] beforeinstallprompt disparado');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        // Listener para instalação bem-sucedida
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App instalado com sucesso!');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showSuccessMessage();
        });
        
        // Para iOS, mostra instruções manuais
        if (this.isIOS && !this.isInstalled) {
            this.showIOSInstructions();
        }
    }
    
    showInstallButton() {
        // Verifica se já foi dispensado antes
        const dismissed = localStorage.getItem('pwa-install-dismissed');
        if (dismissed) {
            const dismissedDate = new Date(dismissed);
            const now = new Date();
            const daysSince = (now - dismissedDate) / (1000 * 60 * 60 * 24);
            
            // Mostra novamente após 7 dias
            if (daysSince < 7) {
                return;
            }
        }
        
        // Cria botão de instalação
        this.installButton = document.createElement('div');
        this.installButton.id = 'pwaInstallPrompt';
        this.installButton.className = 'fixed bottom-4 right-4 z-50 animate-slideUp';
        this.installButton.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-4 max-w-sm border-2 border-primary dark:border-secondary">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-primary dark:bg-secondary rounded-xl flex items-center justify-center">
                            <i class="fas fa-download text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                            Instalar SmartPark
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                            Adicione à tela inicial para acesso rápido e experiência offline
                        </p>
                        <div class="flex space-x-2">
                            <button id="pwaInstallBtn" class="flex-1 bg-primary dark:bg-secondary hover:bg-blue-900 dark:hover:bg-green-600 text-white text-xs font-semibold py-2 px-3 rounded-lg transition">
                                <i class="fas fa-plus mr-1"></i> Instalar
                            </button>
                            <button id="pwaDismissBtn" class="px-3 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.installButton);
        
        // Event listeners
        document.getElementById('pwaInstallBtn').addEventListener('click', () => {
            this.install();
        });
        
        document.getElementById('pwaDismissBtn').addEventListener('click', () => {
            this.dismiss();
        });
    }
    
    async install() {
        if (!this.deferredPrompt) {
            console.warn('[PWA] Prompt de instalação não disponível');
            return;
        }
        
        // Mostra o prompt nativo
        this.deferredPrompt.prompt();
        
        // Aguarda a escolha do usuário
        const { outcome } = await this.deferredPrompt.userChoice;
        console.log(`[PWA] Escolha do usuário: ${outcome}`);
        
        if (outcome === 'accepted') {
            console.log('[PWA] Usuário aceitou a instalação');
        } else {
            console.log('[PWA] Usuário recusou a instalação');
            localStorage.setItem('pwa-install-dismissed', new Date().toISOString());
        }
        
        // Limpa o prompt
        this.deferredPrompt = null;
        this.hideInstallButton();
    }
    
    dismiss() {
        localStorage.setItem('pwa-install-dismissed', new Date().toISOString());
        this.hideInstallButton();
    }
    
    hideInstallButton() {
        if (this.installButton) {
            this.installButton.remove();
            this.installButton = null;
        }
    }
    
    showSuccessMessage() {
        const message = document.createElement('div');
        message.className = 'fixed top-20 right-4 z-50 bg-green-100 border-l-4 border-green-500 text-green-900 p-4 rounded shadow-lg animate-slideDown';
        message.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-check-circle text-xl mr-2"></i>
                <p class="font-semibold">SmartPark instalado com sucesso!</p>
            </div>
        `;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.remove();
        }, 5000);
    }
    
    showIOSInstructions() {
        // Verifica se já foi dispensado
        const dismissed = localStorage.getItem('pwa-ios-dismissed');
        if (dismissed) {
            return;
        }
        
        // Aguarda 3 segundos antes de mostrar
        setTimeout(() => {
            const modal = document.createElement('div');
            modal.id = 'iosInstallModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end sm:items-center justify-center p-4';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-3xl max-w-md w-full p-6 animate-slideUp">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            Instalar SmartPark
                        </h3>
                        <button id="iosModalClose" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Para instalar o SmartPark no seu iPhone/iPad:
                    </p>
                    
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-300 font-bold">1</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Toque no botão <i class="fas fa-share text-blue-500"></i> <strong>Compartilhar</strong> na barra inferior
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-300 font-bold">2</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Role para baixo e toque em <i class="fas fa-plus-square text-blue-500"></i> <strong>"Adicionar à Tela de Início"</strong>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-300 font-bold">3</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Toque em <strong>"Adicionar"</strong> no canto superior direito
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <button id="iosModalDismiss" class="w-full bg-primary dark:bg-secondary hover:bg-blue-900 dark:hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition">
                        Entendi
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Event listeners
            const closeModal = () => {
                localStorage.setItem('pwa-ios-dismissed', 'true');
                modal.remove();
            };
            
            document.getElementById('iosModalClose').addEventListener('click', closeModal);
            document.getElementById('iosModalDismiss').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }, 3000);
    }
}

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PWAInstaller();
    });
} else {
    new PWAInstaller();
}

// Adiciona estilos de animação
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .animate-slideUp {
        animation: slideUp 0.3s ease-out;
    }
    
    .animate-slideDown {
        animation: slideDown 0.3s ease-out;
    }
`;
document.head.appendChild(style);
