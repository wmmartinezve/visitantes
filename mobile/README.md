# Visitantes Mobile

App Flutter (Material 3) para **Anfitrión** y **Centro de Acopio** — Anzoátegui, Venezuela.

## Requisitos

- Flutter SDK ≥ 3.10
- Backend desplegado (Railway) o Laravel local para desarrollo

## URL de la API

La app **no muestra ni permite editar** la URL del servidor.

| Modo | URL |
|------|-----|
| **Release (APK producción)** | Fijada en `lib/config/production_env.dart` → Railway |
| **Debug emulador Android** | `http://10.0.2.2:8000/api/mobile` |
| **Debug simulador iOS** | `http://127.0.0.1:8000/api/mobile` |
| **Debug dispositivo físico** | Misma URL Railway que release |

Tras desplegar en Railway, actualice `kProductionApiBaseUrl` en `production_env.dart` y recompile.

Ver [docs/RAILWAY.md](../docs/RAILWAY.md) para el despliegue completo.

## Desarrollo local

```bash
cd mobile
flutter pub get
flutter run
```

Backend local: `php artisan serve` (emulador) o `./scripts/serve-lan.sh` (dispositivo físico + override debug).

## Build Android (APK release)

```bash
cd mobile
flutter build apk --release
```

Opcional — override en compile time:

```bash
flutter build apk --release --dart-define-from-file=dart_defines/prod.json
```

APK: `build/app/outputs/flutter-apk/app-release.apk`

## Build iOS

```bash
flutter build ios --release
```

Abra `ios/Runner.xcworkspace` en Xcode para firmar.

## Credenciales (demo Railway / local)

| Rol | Email | Password |
|-----|-------|----------|
| Anfitrión | anfitrion@visitantes.test | password |
| Acopio | acopio@visitantes.test | password |

## Funciones offline

- Catálogo geográfico e inventario local en Hive
- Cola de sincronización visible en Inicio
- Botón **sync** en la barra superior
- Tipos en cola: registro invitado, requerimiento, inventario, ajuste de stock, entrega
