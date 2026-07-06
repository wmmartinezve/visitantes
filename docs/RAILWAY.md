# Despliegue en Railway

Backend Laravel (API móvil + panel Filament `/admin`) para **Visitantes · Anzoátegui**.

## 1. Crear proyecto en Railway

1. [railway.app](https://railway.app) → **New Project**
2. **Deploy from GitHub repo** → seleccione este repositorio
3. **Add service** → **Database** → **PostgreSQL**
4. En el servicio web, **Variables** → **Add Reference** → enlace `DATABASE_URL` desde PostgreSQL

## 2. Variables de entorno (servicio web)

| Variable | Valor |
|----------|--------|
| `APP_NAME` | `Visitantes` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generar: `php artisan key:generate --show` |
| `APP_URL` | `https://visitantes.proyectoswm.com` |
| `APP_TIMEZONE` | `America/Caracas` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | Referencia al plugin PostgreSQL |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `FILESYSTEM_DISK` | `public` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `LOG_CHANNEL` | `stderr` |
| `RUN_SEED` | `true` *(solo primer deploy; luego `false`)* |

Railway inyecta `PORT` automáticamente.

## 3. Dominio público

Servicio Railway: **`visitantes-anz`**

Dominio de producción: **`https://visitantes.proyectoswm.com`**

En Railway → servicio `visitantes-anz` → **Settings** → **Networking** → **Custom Domain** → agregue `visitantes.proyectoswm.com` y configure el registro DNS (CNAME) que indique Railway.

| Recurso | URL |
|---------|-----|
| Panel admin | `https://visitantes.proyectoswm.com/admin` |
| API móvil | `https://visitantes.proyectoswm.com/api/mobile` |
| Health | `https://visitantes.proyectoswm.com/up` |

Confirme que `APP_URL=https://visitantes.proyectoswm.com` en las variables del servicio.

## 4. Primer deploy

Con `RUN_SEED=true`, el entrypoint ejecuta migraciones y seeders:

- Geografía de Anzoátegui
- Datos demo (refugios, centros, inventario)
- Usuario admin: `admin@visitantes.test` / `password`

Tras verificar el panel, ponga `RUN_SEED=false` para evitar re-seeds en redeploys.

## 5. App móvil (Flutter)

La URL de la API **no se muestra** en la app. Está fijada en:

`mobile/lib/config/production_env.dart` → `https://visitantes.proyectoswm.com/api/mobile`

Para compilar release:

```bash
cd mobile
flutter build apk --release
```

El APK quedará en `build/app/outputs/flutter-apk/app-release.apk`.

### Credenciales demo en producción

| Rol | Email | Password |
|-----|-------|----------|
| Admin (web) | admin@visitantes.test | password |
| Anfitrión (app) | anfitrion@visitantes.test | password |
| Acopio (app) | acopio@visitantes.test | password |

**Cambie las contraseñas** antes de uso real.

## 6. Almacenamiento de fotos

Railway usa disco efímero. Las fotos de Invitados en `storage/app/public` pueden perderse al redeploy.

Para producción estable:

- Monte un **Volume** en Railway en `/app/storage/app/public`, o
- Configure un bucket S3 y `FILESYSTEM_DISK=s3`

## 7. Desarrollo local (opcional)

```bash
./scripts/serve-lan.sh   # solo para pruebas locales
php artisan serve        # emulador Android: 10.0.2.2:8000
```

En debug, la app Flutter usa localhost/emulador automáticamente; en release apunta a `visitantes.proyectoswm.com`.

## 8. Comandos útiles

```bash
# Logs Railway
railway logs

# Shell en el contenedor
railway run php artisan tinker

# Forzar migraciones
railway run php artisan migrate --force
```
