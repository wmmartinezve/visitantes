import { csrfToken, refreshCatalog } from './catalog';
import { listQueue, queueCount, removeFromQueue } from './db';

function config() {
    return window.VisitantesOffline ?? {};
}

let syncing = false;

export async function syncQueue() {
    if (syncing || !navigator.onLine) {
        return { ok: 0, failed: 0, skipped: true };
    }

    const { syncUrl } = config();
    if (!syncUrl) {
        return { ok: 0, failed: 0, skipped: true };
    }

    const pending = await listQueue();
    if (pending.length === 0) {
        return { ok: 0, failed: 0, skipped: false };
    }

    syncing = true;
    window.dispatchEvent(new CustomEvent('visitantes:sync-start'));

    let totalOk = 0;
    let totalFailed = 0;

    try {
        const batchSize = 10;

        for (let i = 0; i < pending.length; i += batchSize) {
            const batch = pending.slice(i, i + batchSize);

            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    items: batch.map(({ client_id, type, payload }) => ({
                        client_id,
                        type,
                        payload,
                    })),
                }),
            });

            if (!response.ok) {
                throw new Error('Error al sincronizar con el servidor.');
            }

            const data = await response.json();

            for (const result of data.results ?? []) {
                if (result.status === 'ok') {
                    await removeFromQueue(result.client_id);
                    totalOk += 1;
                } else {
                    totalFailed += 1;
                }
            }
        }

        await refreshCatalog(true);

        window.dispatchEvent(new CustomEvent('visitantes:sync-complete', {
            detail: { ok: totalOk, failed: totalFailed },
        }));

        window.dispatchEvent(new CustomEvent('visitantes:queue-changed'));

        return { ok: totalOk, failed: totalFailed, skipped: false };
    } catch (error) {
        console.warn('[offline] sync:', error);
        window.dispatchEvent(new CustomEvent('visitantes:sync-error', { detail: error }));
        return { ok: totalOk, failed: totalFailed, skipped: false, error };
    } finally {
        syncing = false;
    }
}

export async function pendingCount() {
    return queueCount();
}

export function isSyncing() {
    return syncing;
}
