import {
    addToQueue,
    collectInvitadoPayload,
    collectInventarioCreatePayload,
    collectInventarioUpdatePayload,
    collectRequerimientoPayload,
} from './queue';

function showOfflineToast(message, type = 'info') {
    window.dispatchEvent(new CustomEvent('visitantes:offline-toast', {
        detail: { message, type },
    }));
}

async function handleOfflineSubmit(form) {
    const type = form.dataset.offlineType;

    try {
        let item;

        switch (type) {
            case 'invitado.registro':
                item = await addToQueue(type, await collectInvitadoPayload(form));
                showOfflineToast(`Invitado guardado localmente. Se sincronizará al reconectar.`, 'warning');
                form.reset();
                break;
            case 'requerimiento.create':
                item = await addToQueue(type, collectRequerimientoPayload(form));
                showOfflineToast('Requerimiento guardado localmente.', 'warning');
                form.reset();
                break;
            case 'inventario.create':
                item = await addToQueue(type, collectInventarioCreatePayload(form));
                showOfflineToast('Ítem de inventario guardado localmente.', 'warning');
                form.reset();
                break;
            case 'inventario.update_cantidad':
                item = await addToQueue(type, collectInventarioUpdatePayload(form));
                showOfflineToast('Actualización de stock guardada localmente.', 'warning');
                break;
            default:
                showOfflineToast('Este formulario no admite registro offline.', 'error');
                return;
        }

        return item;
    } catch (error) {
        console.error('[offline] queue:', error);
        showOfflineToast('No se pudo guardar localmente.', 'error');
    }
}

export function registerOfflineForms() {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-offline-form]');
        if (!form) {
            return;
        }

        if (navigator.onLine) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        await handleOfflineSubmit(form);
    }, true);
}
