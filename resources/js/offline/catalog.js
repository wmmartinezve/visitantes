import { getCatalog, getMeta, setCatalog, setMeta } from './db';

function config() {
    return window.VisitantesOffline ?? {};
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function refreshCatalog(force = false) {
    if (!navigator.onLine) {
        return getCatalog();
    }

    const { catalogUrl } = config();
    if (!catalogUrl) {
        return null;
    }

    const lastVersion = await getMeta('catalog_version');

    try {
        const response = await fetch(catalogUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('No se pudo descargar el catálogo offline.');
        }

        const catalog = await response.json();

        if (force || catalog.version !== lastVersion) {
            await setCatalog(catalog);
            await setMeta('catalog_version', catalog.version);
            await setMeta('catalog_updated_at', catalog.generated_at);
        }

        window.dispatchEvent(new CustomEvent('visitantes:catalog-updated', { detail: catalog }));

        return catalog;
    } catch (error) {
        console.warn('[offline] catálogo:', error);
        return getCatalog();
    }
}

export async function cachedCatalogSummary() {
    const catalog = await getCatalog();

    if (!catalog) {
        return null;
    }

    return {
        version: catalog.version,
        municipios: catalog.municipios?.length ?? 0,
        parroquias: catalog.parroquias?.length ?? 0,
        refugios: catalog.refugios?.length ?? 0,
        centros_acopio: catalog.centros_acopio?.length ?? 0,
        updated_at: catalog.generated_at,
    };
}

export { csrfToken };
