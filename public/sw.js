const CACHE_NAME = 'absensi-cache-v1';
const urlsToCache = [
    '/',
    '/offline',
    '/build/assets/app.css', // Note: We need dynamic versioning here ideally, but for now we try generic or need mechanism
    '/images/icons/icon-192x192.png'
];

// Install SW
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                // We don't fail if some assets are missing/dynamic
                return cache.addAll(urlsToCache).catch(err => console.warn('Some assets failed to cache', err));
            })
    );
});

// Cache and return requests
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }
                return fetch(event.request).catch(() => {
                    // If fetch fails (offline), try to return offline page for navigation requests
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline');
                    }
                });
            })
    );
});

// Update SW
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
