/**
 * Saint Porphyrius Service Worker
 * Enables offline functionality and PWA features
 */

const CACHE_NAME = 'sp-app-v1';
const OFFLINE_URL = '/app/';

// Assets to cache on install
const STATIC_ASSETS = [
  '/app/',
  '/wp-content/plugins/Saint-Porphyrius/assets/css/main.css',
  '/wp-content/plugins/Saint-Porphyrius/assets/css/unified.css',
  '/wp-content/plugins/Saint-Porphyrius/assets/js/main.js',
  '/wp-content/plugins/Saint-Porphyrius/assets/icons/icon-192x192.png',
  '/wp-content/plugins/Saint-Porphyrius/assets/icons/icon-512x512.png',
  'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('SP Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS.filter(url => !url.startsWith('http')));
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => cacheName !== CACHE_NAME)
            .map((cacheName) => caches.delete(cacheName))
        );
      })
      .then(() => self.clients.claim())
  );
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }

  // Skip admin-ajax.php and API requests
  if (event.request.url.includes('admin-ajax.php') || 
      event.request.url.includes('wp-json') ||
      event.request.url.includes('wp-admin')) {
    return;
  }

  // Handle navigation requests (HTML pages)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return caches.match(OFFLINE_URL);
        })
    );
    return;
  }

  // Handle other requests - network first, cache fallback
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Cache successful responses for static assets
        if (response.ok && event.request.method === 'GET') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request);
      })
  );
});

// Handle push notifications (for future use)
self.addEventListener('push', (event) => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body || 'لديك إشعار جديد',
      icon: '/wp-content/plugins/Saint-Porphyrius/assets/icons/icon-192x192.png',
      badge: '/wp-content/plugins/Saint-Porphyrius/assets/icons/icon-72x72.png',
      vibrate: [100, 50, 100],
      dir: 'rtl',
      lang: 'ar',
      data: {
        url: data.url || '/app/'
      }
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title || 'القديس بورفيريوس', options)
    );
  }
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url || '/app/')
  );
});
