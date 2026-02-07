// SmartPark Service Worker - v2.0
// PWA com suporte offline e cache inteligente

const VERSION = 'v2.0.0';
const CACHE_STATIC = `smartpark-static-${VERSION}`;
const CACHE_DYNAMIC = `smartpark-dynamic-${VERSION}`;
const CACHE_IMAGES = `smartpark-images-${VERSION}`;

const OFFLINE_URL = '/smartpark/offline.html';

// Recursos essenciais para cache imediato (instalação)
const STATIC_ASSETS = [
    '/smartpark/',
    '/smartpark/index.php',
    '/smartpark/manifest.json',
    '/smartpark/assets/icons/icon-192.png',
    '/smartpark/assets/icons/icon-512.png',
    '/smartpark/offline.html',
    '/smartpark/css/pwa-install.css',
    'https://cdn.tailwindcss.com',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
];

// Páginas dinâmicas para cache sob demanda
const DYNAMIC_PAGES = [
    '/smartpark/admin/dashboard.php',
    '/smartpark/funcionario/dashboard.php',
    '/smartpark/usuario/dashboard.php',
    '/smartpark/usuario/buscar-vagas.php',
    '/smartpark/usuario/minhas-reservas.php'
];

// ==========================================
// INSTALAÇÃO DO SERVICE WORKER
// ==========================================
self.addEventListener('install', event => {
    console.log(`[Service Worker] Instalando versão ${VERSION}...`);
    
    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then(cache => {
                console.log('[Service Worker] Cacheando recursos estáticos');
                // Usa addAll com {cache: 'reload'} para forçar atualização
                return cache.addAll(
                    STATIC_ASSETS.map(url => new Request(url, {cache: 'reload'}))
                ).catch(err => {
                    console.warn('[Service Worker] Alguns recursos falharam no cache:', err);
                    // Continua mesmo com falhas parciais
                });
            })
            .then(() => {
                console.log(`[Service Worker] Versão ${VERSION} instalada com sucesso`);
                return self.skipWaiting(); // Ativa imediatamente
            })
    );
});

// ==========================================
// ATIVAÇÃO DO SERVICE WORKER
// ==========================================
self.addEventListener('activate', event => {
    console.log(`[Service Worker] Ativando versão ${VERSION}...`);
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                // Remove caches antigos
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName.startsWith('smartpark-') && 
                            cacheName !== CACHE_STATIC && 
                            cacheName !== CACHE_DYNAMIC && 
                            cacheName !== CACHE_IMAGES) {
                            console.log('[Service Worker] Removendo cache antigo:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log(`[Service Worker] Versão ${VERSION} ativada`);
                return self.clients.claim(); // Assume controle imediatamente
            })
    );
});

// ==========================================
// INTERCEPTAÇÃO DE REQUISIÇÕES (FETCH)
// ==========================================
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignora requisições não-GET
    if (request.method !== 'GET') {
        return;
    }
    
    // Ignora requisições de outros domínios (exceto CDNs conhecidos)
    if (!url.origin.includes(self.location.origin) && 
        !url.origin.includes('cdn.tailwindcss.com') &&
        !url.origin.includes('cdnjs.cloudflare.com') &&
        !url.origin.includes('cdn.jsdelivr.net') &&
        !url.origin.includes('fonts.googleapis.com') &&
        !url.origin.includes('fonts.gstatic.com')) {
        return;
    }
    
    // ==========================================
    // ESTRATÉGIA: NAVEGAÇÃO (páginas HTML/PHP)
    // Network First com fallback para cache e offline
    // ==========================================
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cacheia a resposta bem-sucedida
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_DYNAMIC)
                            .then(cache => cache.put(request, responseClone));
                    }
                    return response;
                })
                .catch(() => {
                    // Offline: tenta cache, depois página offline
                    return caches.match(request)
                        .then(cachedResponse => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            // Retorna página offline
                            return caches.match(OFFLINE_URL);
                        });
                })
        );
        return;
    }
    
    // ==========================================
    // ESTRATÉGIA: IMAGENS
    // Cache First com fallback para rede
    // ==========================================
    if (request.destination === 'image') {
        event.respondWith(
            caches.match(request)
                .then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    return fetch(request)
                        .then(response => {
                            if (response.ok) {
                                const responseClone = response.clone();
                                caches.open(CACHE_IMAGES)
                                    .then(cache => cache.put(request, responseClone));
                            }
                            return response;
                        })
                        .catch(() => {
                            // Fallback: ícone padrão
                            return caches.match('/smartpark/assets/icons/icon-192.png');
                        });
                })
        );
        return;
    }
    
    // ==========================================
    // ESTRATÉGIA: CSS, JS, FONTS
    // Cache First (recursos estáticos)
    // ==========================================
    if (request.destination === 'style' || 
        request.destination === 'script' || 
        request.destination === 'font') {
        event.respondWith(
            caches.match(request)
                .then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    return fetch(request)
                        .then(response => {
                            if (response.ok) {
                                const responseClone = response.clone();
                                caches.open(CACHE_STATIC)
                                    .then(cache => cache.put(request, responseClone));
                            }
                            return response;
                        });
                })
        );
        return;
    }
    
    // ==========================================
    // ESTRATÉGIA: OUTROS RECURSOS
    // Network First com cache de backup
    // ==========================================
    event.respondWith(
        fetch(request)
            .then(response => {
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_DYNAMIC)
                        .then(cache => cache.put(request, responseClone));
                }
                return response;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});

// ==========================================
// MENSAGENS (para controle de atualização)
// ==========================================
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('[Service Worker] Pulando espera e ativando nova versão');
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: VERSION });
    }
});

// ==========================================
// BACKGROUND SYNC (preparação para futuro)
// ==========================================
self.addEventListener('sync', event => {
    console.log('[Service Worker] Background sync:', event.tag);
    
    if (event.tag === 'sync-reservations') {
        event.waitUntil(syncReservations());
    }
});

async function syncReservations() {
    // Placeholder para sincronização futura
    console.log('[Service Worker] Sincronizando reservas...');
}

// ==========================================
// NOTIFICAÇÕES PUSH (preparação para futuro)
// ==========================================
self.addEventListener('push', event => {
    console.log('[Service Worker] Push recebido:', event);
    
    const options = {
        body: event.data ? event.data.text() : 'Nova notificação do SmartPark',
        icon: '/smartpark/assets/icons/icon-192.png',
        badge: '/smartpark/assets/icons/icon-192.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Ver detalhes',
                icon: '/smartpark/assets/icons/icon-192.png'
            },
            {
                action: 'close',
                title: 'Fechar',
                icon: '/smartpark/assets/icons/icon-192.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('SmartPark', options)
    );
});

self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notificação clicada:', event.action);
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/smartpark/usuario/dashboard.php')
        );
    }
});