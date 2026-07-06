import { enqueue, listQueue, queueCount, removeFromQueue, uuid } from './db';

export async function addToQueue(type, payload) {
    const item = {
        client_id: uuid(),
        type,
        payload,
        created_at: new Date().toISOString(),
    };

    await enqueue(item);

    window.dispatchEvent(new CustomEvent('visitantes:queue-changed'));

    return item;
}

export { listQueue, queueCount, removeFromQueue };

async function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

export async function collectInvitadoPayload(form) {
    const payload = {
        nombre: form.querySelector('[data-offline-field="nombre"]')?.value?.trim() ?? '',
        apellido: form.querySelector('[data-offline-field="apellido"]')?.value?.trim() ?? '',
        cedula: form.querySelector('[data-offline-field="cedula"]')?.value?.trim() || null,
        telefono: form.querySelector('[data-offline-field="telefono"]')?.value?.trim() || null,
        fecha_nacimiento: form.querySelector('[data-offline-field="fecha_nacimiento"]')?.value ?? '',
        familiares: [],
    };

    form.querySelectorAll('[data-offline-familiar]').forEach((block) => {
        const familiar = {
            nombre: block.querySelector('[data-offline-field="nombre"]')?.value?.trim() ?? '',
            apellido: block.querySelector('[data-offline-field="apellido"]')?.value?.trim() ?? '',
            parentesco: block.querySelector('[data-offline-field="parentesco"]')?.value?.trim() ?? '',
            cedula: block.querySelector('[data-offline-field="cedula"]')?.value?.trim() || null,
            telefono: block.querySelector('[data-offline-field="telefono"]')?.value?.trim() || null,
            fecha_nacimiento: block.querySelector('[data-offline-field="fecha_nacimiento"]')?.value ?? '',
        };

        if (familiar.nombre && familiar.apellido) {
            payload.familiares.push(familiar);
        }
    });

    const fotoInput = form.querySelector('[data-offline-field="foto"]');
    const file = fotoInput?.files?.[0];

    if (file) {
        payload.foto_base64 = await fileToBase64(file);
        payload.foto_mime = file.type || 'image/jpeg';
    }

    return payload;
}

export function collectRequerimientoPayload(form) {
    return {
        invitado_id: Number(form.dataset.offlineInvitadoId || 0) || null,
        invitado_client_id: form.dataset.offlineInvitadoClientId || null,
        categoria: form.querySelector('[data-offline-field="categoria"]')?.value?.trim() ?? '',
        subcategoria: form.querySelector('[data-offline-field="subcategoria"]')?.value?.trim() ?? '',
        cantidad: Number(form.querySelector('[data-offline-field="cantidad"]')?.value ?? 1),
    };
}

export function collectInventarioCreatePayload(form) {
    return {
        categoria: form.querySelector('[data-offline-field="categoria"]')?.value?.trim() ?? '',
        subcategoria: form.querySelector('[data-offline-field="subcategoria"]')?.value?.trim() ?? '',
        cantidad: Number(form.querySelector('[data-offline-field="cantidad"]')?.value ?? 0),
        unidad_medida: form.querySelector('[data-offline-field="unidad_medida"]')?.value?.trim() ?? 'unidad',
    };
}

export function collectInventarioUpdatePayload(form) {
    return {
        inventario_id: Number(form.dataset.offlineInventarioId || 0),
        cantidad: Number(form.querySelector('[data-offline-field="cantidad"]')?.value ?? 0),
    };
}
