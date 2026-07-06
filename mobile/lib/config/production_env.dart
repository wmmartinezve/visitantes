/// URL pública del backend desplegado en Railway.
///
/// Tras el primer deploy, reemplace el dominio con el generado en Railway
/// (Settings → Networking → Public domain) y recompile la app móvil.
const String kProductionApiBaseUrl =
    'https://visitantes-production.up.railway.app/api/mobile';

/// Dominio público del backend (sin path). Usado para documentación interna.
const String kProductionAppHost = 'visitantes-production.up.railway.app';
