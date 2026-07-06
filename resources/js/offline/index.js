import { cachedCatalogSummary, refreshCatalog } from './catalog';
import { registerOfflineForms } from './forms';
import { pendingCount, syncQueue } from './sync';

function bannerEl() {
    return document.getElementById('offline-status-banner');
}

function renderBanner() {
    const el = bannerEl();
    if (!el) {
        return;
    }

    Promise.all([pendingCount(), cachedCatalogSummary()]).then(([pending, catalog]) => {
        const online = navigator.onLine;

        el.classList.remove('hidden', 'bg-m3-error-container', 'bg-m3-warning-container', 'bg-m3-primary-container');
        el.classList.add('text-sm');

        if (!online) {
            el.classList.add('bg-m3-error-container', 'text-m3-on-error-container');
            el.innerHTML = `
                <div class="flex items-center gap-2 px-4 py-2">
                    <span class="material-symbols-outlined text-base">cloud_off</span>
                    <span>Sin conexión — el registro se guardará en el teléfono y se sincronizará al volver internet.</span>
                </div>`;
            return;
        }

        if (pending > 0) {
            el.classList.add('bg-m3-warning-container', 'text-m3-on-surface');
            el.innerHTML = `
                <div class="flex items-center justify-between gap-2 px-4 py-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">sync</span>
                        <span>${pending} registro(s) pendiente(s) de sincronizar</span>
                    </div>
                    <button type="button" id="offline-sync-now" class="rounded-full bg-m3-primary px-3 py-1 text-xs font-medium text-m3-on-primary">
                        Sincronizar
                    </button>
                </div>`;

            document.getElementById('offline-sync-now')?.addEventListener('click', () => {
                syncQueue();
            }, { once: true });

            return;
        }

        if (catalog) {
            el.classList.add('bg-m3-primary-container', 'text-m3-on-primary-container');
            el.innerHTML = `
                <div class="flex items-center gap-2 px-4 py-2 text-xs">
                    <span class="material-symbols-outlined text-base">offline_pin</span>
                    <span>Datos offline listos: ${catalog.municipios} municipios, ${catalog.parroquias} parroquias, ${catalog.centros_acopio} centros de acopio</span>
                </div>`;
            return;
        }

        el.classList.add('hidden');
    });
}

function registerUiEvents() {
    window.addEventListener('online', () => {
        renderBanner();
        syncQueue();
        refreshCatalog(true);
    });

    window.addEventListener('offline', renderBanner);

    window.addEventListener('visitantes:queue-changed', renderBanner);
    window.addEventListener('visitantes:sync-complete', (event) => {
        renderBanner();
        const { ok } = event.detail ?? {};
        if (ok > 0) {
            showToast(`${ok} registro(s) sincronizado(s) correctamente.`, 'success');
        }
    });
    window.addEventListener('visitantes:sync-error', () => {
        showToast('No se pudo sincronizar. Se reintentará al reconectar.', 'error');
    });
    window.addEventListener('visitantes:offline-toast', (event) => {
        showToast(event.detail?.message ?? '', event.detail?.type ?? 'info');
    });
    window.addEventListener('visitantes:catalog-updated', renderBanner);
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-24 left-4 right-4 z-50 mx-auto max-w-lg rounded-xl px-4 py-3 text-sm shadow-lg';

    const classes = {
        success: 'bg-m3-primary text-m3-on-primary',
        warning: 'bg-m3-warning-container text-m3-on-surface',
        error: 'bg-m3-error-container text-m3-on-error-container',
        info: 'bg-m3-surface-container-highest text-m3-on-surface',
    };

    toast.classList.add(...(classes[type] ?? classes.info).split(' '));
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 4000);
}

export function initOffline() {
    if (!window.VisitantesOffline) {
        return;
    }

    registerOfflineForms();
    registerUiEvents();
    renderBanner();

    if (navigator.onLine) {
        refreshCatalog(true).then(() => syncQueue());
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
}
