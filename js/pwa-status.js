// SmartPark PWA Status Monitor
// Monitora status online/offline e sincronização

class PWAStatus {
    constructor() {
        this.isOnline = navigator.onLine;
        this.statusIndicator = null;
        this.init();
    }
    
    init() {
        this.createStatusIndicator();
        this.attachEventListeners();
        this.updateStatus();
        
        // Verifica service worker
        this.checkServiceWorker();
    }
    
    createStatusIndicator() {
        // Cria indicador de status na navbar
        const indicator = document.createElement('div');
        indicator.id = 'pwaStatusIndicator';
        indicator.className = 'hidden md:flex items-center space-x-2 px-3 py-2 rounded-md text-sm';
        indicator.innerHTML = `
            <div id="statusDot" class="w-2 h-2 rounded-full"></div>
            <span id="statusLabel"></span>
        `;
        
        this.statusIndicator = indicator;
        
        // Insere na navbar (procura pelo dark mode toggle)
        const navbar = document.querySelector('nav .hidden.md\\:flex.items-center');
        if (navbar) {
            navbar.insertBefore(indicator, navbar.firstChild);
        }
    }
    
    attachEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateStatus();
            this.showNotification('Conexão restaurada', 'success');
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateStatus();
            this.showNotification('Você está offline', 'warning');
        });
    }
    
    updateStatus() {
        const dot = document.getElementById('statusDot');
        const label = document.getElementById('statusLabel');
        
        if (!dot || !label) return;
        
        if (this.isOnline) {
            dot.className = 'w-2 h-2 rounded-full bg-green-500';
            label.textContent = 'Online';
            label.className = 'text-green-600 dark:text-green-400 font-medium';
        } else {
            dot.className = 'w-2 h-2 rounded-full bg-red-500 animate-pulse';
            label.textContent = 'Offline';
            label.className = 'text-red-600 dark:text-red-400 font-medium';
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const colors = {
            success: 'bg-green-100 border-green-500 text-green-900',
            warning: 'bg-yellow-100 border-yellow-500 text-yellow-900',
            error: 'bg-red-100 border-red-500 text-red-900',
            info: 'bg-blue-100 border-blue-500 text-blue-900'
        };
        
        notification.className = `fixed top-20 right-4 z-50 ${colors[type]} border-l-4 p-4 rounded shadow-lg animate-slideIn`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                <p class="font-semibold">${message}</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    async checkServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.ready;
                console.log('[PWA Status] Service Worker ativo:', registration);
                
                // Verifica atualizações
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    console.log('[PWA Status] Nova versão encontrada');
                    
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.warn('[PWA Status] Service Worker não disponível:', error);
            }
        }
    }
    
    showUpdateNotification() {
        const notification = document.createElement('div');
        notification.className = 'fixed bottom-4 left-4 z-50 bg-blue-600 text-white rounded-lg shadow-2xl p-4 max-w-sm animate-slideIn';
        notification.innerHTML = `
            <div class="flex items-start space-x-3">
                <i class="fas fa-sync-alt text-2xl mt-1"></i>
                <div class="flex-1">
                    <h4 class="font-semibold mb-1">Nova versão disponível</h4>
                    <p class="text-sm text-blue-100 mb-3">Uma atualização do SmartPark está pronta para ser instalada.</p>
                    <button id="updateBtn" class="w-full bg-white text-blue-600 font-semibold py-2 px-4 rounded hover:bg-blue-50 transition">
                        Atualizar Agora
                    </button>
                </div>
                <button id="dismissUpdate" class="text-blue-200 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        document.getElementById('updateBtn').addEventListener('click', () => {
            window.location.reload();
        });
        
        document.getElementById('dismissUpdate').addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PWAStatus();
    });
} else {
    new PWAStatus();
}

// Adiciona estilos de animação
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .animate-slideIn {
        animation: slideIn 0.3s ease-out;
    }
`;
document.head.appendChild(style);
