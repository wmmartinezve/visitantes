const CACHE = 'visitantes-static-v1';

const STATIC_ASSETS = [
    '/manifest.json',
    '/offline.html',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(STATIC_ASSETS)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))),
        ),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }

                        return response;
                    })
                    .catch(() => cached);

                return cached ?? network;
            }),
        );

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );
    }
});
