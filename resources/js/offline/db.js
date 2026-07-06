const DB_NAME = 'visitantes-offline';
const DB_VERSION = 1;

let dbPromise = null;

function openDb() {
    if (dbPromise) {
        return dbPromise;
    }

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains('catalog')) {
                db.createObjectStore('catalog');
            }

            if (!db.objectStoreNames.contains('queue')) {
                const store = db.createObjectStore('queue', { keyPath: 'client_id' });
                store.createIndex('created_at', 'created_at');
            }

            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta');
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

async function tx(storeName, mode, fn) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, mode);
        const store = transaction.objectStore(storeName);
        const result = fn(store);

        transaction.oncomplete = () => resolve(result);
        transaction.onerror = () => reject(transaction.error);
    });
}

export async function getCatalog() {
    return tx('catalog', 'readonly', (store) => store.get('current'));
}

export async function setCatalog(catalog) {
    return tx('catalog', 'readwrite', (store) => {
        store.put(catalog, 'current');
    });
}

export async function getMeta(key) {
    return tx('meta', 'readonly', (store) => store.get(key));
}

export async function setMeta(key, value) {
    return tx('meta', 'readwrite', (store) => {
        store.put(value, key);
    });
}

export async function enqueue(item) {
    return tx('queue', 'readwrite', (store) => {
        store.put(item);
    });
}

export async function listQueue() {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction('queue', 'readonly');
        const store = transaction.objectStore('queue');
        const request = store.getAll();

        request.onsuccess = () => {
            const items = request.result ?? [];
            items.sort((a, b) => String(a.created_at).localeCompare(String(b.created_at)));
            resolve(items);
        };

        request.onerror = () => reject(request.error);
    });
}

export async function removeFromQueue(clientId) {
    return tx('queue', 'readwrite', (store) => {
        store.delete(clientId);
    });
}

export async function clearQueue() {
    return tx('queue', 'readwrite', (store) => {
        store.clear();
    });
}

export async function queueCount() {
    const items = await listQueue();
    return items.length;
}

export function uuid() {
    return crypto.randomUUID();
}
