# Visitantes — Prompt Maestro

> **Última actualización:** 2026-07-12 (Multi-hogar anfitrión · wizard Flutter)  
> **Ámbito territorial:** estado **Anzoátegui**, Venezuela  
> **Estado general:** 🟡 Fase 8 — App Flutter móvil (reemplaza Livewire/PWA de campo)  
> **Documento vivo:** actualiza checkboxes, fechas y notas al cerrar cada tarea.  
> **Fuente base:** `docs/plan_desarrollo_cursor_laravel.pdf`

---

## Cómo usar este documento

1. **Cursor / IA:** Lee este archivo al inicio de cada sesión. Asume todo el contexto aquí descrito.
2. **Seguimiento:** Marca `[x]` al completar tareas. Anota fecha y commit en *Notas de avance*.
3. **Al pedir una tarea:** Indica la fase y el ítem, ej.: *"Implementa Fase 1 · migraciones invitados"*.
4. **Al terminar una sesión:** Actualiza *Estado general*, *Fase activa* y *Notas de avance*.

---

## 1. Rol del agente

Actúa como desarrollador **Full-Stack Senior** experto en:

- **Laravel 11** (migraciones, modelos, policies, servicios)
- **Filament PHP v3** (panel administrativo centralizado)
- **Livewire v3 + Tailwind CSS** (interfaz móvil de campo)
- Arquitectura de bases de datos relacional con seeders geográficos

**Idioma:** responde en español.  
**Modo de trabajo:** incremental — una fase, un módulo o un archivo a la vez. No entregues el proyecto completo de una sola vez para evitar bloqueos por exceso de contexto o límites de tokens.

### Formato de respuesta en cada tarea

1. **Qué hacemos** (1–2 líneas)
2. **Archivos** a crear o modificar
3. **Código** (solo lo necesario para esta tarea)
4. **Cómo probarlo**
5. **Siguiente paso sugerido**

---

## 2. Visión del producto

**Sistema de Asistencia y Gestión de Invitados** para contingencias en Venezuela. Permite registrar ciudadanos bajo atención en **hogares solidarios** (familiares o amigos que los reciben), gestionar inventarios en centros de acopio y emparejar requerimientos de insumos con existencias disponibles.

### 2.1 Las tres entidades del sistema

El ecosistema se compone de **tres actores operativos** con interfaces y responsabilidades distintas:

| # | Entidad | Interfaz | Responsabilidad principal |
|---|---------|----------|---------------------------|
| **1** | **Panel administrativo** | Filament v3 (web) | Gestión central de todo: catálogo geográfico, hogares solidarios, centros de acopio, invitados, inventarios globales, asignación de requerimientos, reportes y usuarios |
| **2** | **Centros de acopio** | App móvil/responsiva (Livewire) | Operación en almacén: registrar y actualizar inventario del centro, consultar existencias, recibir asignaciones. El centro debe registrarse con **municipio, parroquia, dirección y georreferenciación** |
| **3** | **Anfitrión** | App móvil/responsiva (Livewire) | Operación en hogar solidario: registrar **Invitados** (y núcleo familiar), dar seguimiento a hospedados y **gestionar sus requerimientos** (agua, ropa, alimentos, etc.) |

**Flujo operativo resumido:**

```
Anfitrión (hogar solidario) registra Invitado → crea Requerimiento
        ↓
Panel administrativo empareja Requerimiento con Centro de acopio (por stock y proximidad)
        ↓
Centro de acopio despacha / actualiza inventario desde su app
```

> Un **anfitrión** puede gestionar **varios hogares solidarios** (cada uno con un único núcleo), con un **hogar activo** para registrar Invitados. Un operador de **centro de acopio** está vinculado a un **centro de acopio** concreto. El **administrador** no opera en campo; gobierna desde el panel.

### 2.2 Enfoque arquitectónico y modular

La aplicación se segmenta en **4 fases incrementales**. Toda la gestión geográfica (Municipios y Parroquias de Venezuela) se maneja mediante tablas normalizadas y editables desde el **panel administrativo**, alimentando selectores dinámicos en la **app de anfitrión** y en la **app de centros de acopio**.

Tanto **hogares solidarios** como **centros de acopio** comparten el patrón territorial: `municipio → parroquia → comuna → dirección exacta → latitud/longitud`. Los Invitados registran **procedencia** (`estado → municipio → parroquia`, catálogo INE) y **situación laboral del jefe de familia** al momento de la tragedia.

### 2.3 Regla CRÍTICA de terminología

> **Queda estrictamente prohibido** el uso de los términos **"refugiado"**, **"damnificado"** o **"afectado"**.  
> El sistema se referirá a los ciudadanos bajo atención exclusivamente como **"Invitados"** u **"Hospedados"**.

Esta regla aplica en: modelos, migraciones, labels de Filament, vistas Livewire, comentarios de código, documentación y respuestas del agente.

---

## 3. Stack acordado

| Capa | Tecnología | Estado |
|------|------------|--------|
| Backend | Laravel 11, PHP 8.2+ | ✅ Instalado |
| Base de datos | PostgreSQL (prod) / SQLite (dev) | ✅ SQLite en dev |
| **Entidad 1:** Panel admin | Filament PHP v3 | ✅ Fase 2 |
| **Entidad 2:** App centros de acopio | **Flutter** (Material 3) + API Laravel | 🟡 En desarrollo |
| **Entidad 3:** App anfitrión | **Flutter** (Material 3) + API Laravel | 🟡 En desarrollo |
| Auth / roles | Laravel Sanctum (mobile) + Filament session (admin) | ✅ |
| Storage fotos | disco `public` | ✅ Fase 3 |
| Compresión imágenes | `browser-image-compression` (CDN) | ✅ Fase 3 |

---

## 4. Modelo de datos

### 4.1 Diagrama de relaciones

```
municipios ──< parroquias ──< comunas ──< hogares_solidarios ──< invitados (núcleo: jefe + familiares)

**Regla 1:1:** cada `hogar_solidario` acoge **un único núcleo familiar** (exactamente un jefe de familia + sus familiares vinculados).

**Regla multi-hogar:** un anfitrión puede crear **varios** `hogares_solidarios` (`anfitrion_user_id`). `users.hogar_solidario_id` = hogar **activo** (scope de Invitados y wizard). Si el activo ya tiene núcleo, debe registrar **otro hogar** antes de un nuevo núcleo.

users (rol: anfitrion) → hogar_solidario_id (activo) + hogares_solidarios.anfitrion_user_id (todos los suyos)
```

### 4.2 Tablas y reglas de negocio

#### `municipios`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| nombre | VARCHAR | |

#### `parroquias`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| municipio_id | FK → municipios | |
| nombre | VARCHAR | |

#### `refugios`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| nombre | VARCHAR | |
| parroquia_id | FK → parroquias | |
| latitud | DECIMAL(10,8) | |
| longitud | DECIMAL(11,8) | |
| direccion_exacta | TEXT | |
| deleted_at | TIMESTAMP | SoftDeletes |

#### `invitados`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| jefe_familia_id | FK → invitados.id, nullable | autoreferencia familiar |
| nombre | VARCHAR | |
| apellido | VARCHAR | |
| cedula | VARCHAR, nullable | |
| fecha_nacimiento | DATE | |
| telefono | VARCHAR, nullable | |
| foto_ingreso | VARCHAR, nullable | ruta en disco `public` |
| refugio_id | FK → refugios | |
| estatus | ENUM | `Activo`, `Egresado` |
| deleted_at | TIMESTAMP | SoftDeletes |

> Un **jefe de familia** tiene muchos miembros asociados vía `jefe_familia_id`.

#### `centros_acopio`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| nombre | VARCHAR | |
| parroquia_id | FK → parroquias | mismo patrón territorial que refugios |
| direccion_exacta | TEXT | |
| latitud | DECIMAL(10,8) | georreferenciación obligatoria |
| longitud | DECIMAL(11,8) | georreferenciación obligatoria |
| contacto | VARCHAR | teléfono o responsable |
| activo | BOOLEAN | default true |

> El **panel administrativo** crea y valida centros de acopio. Los operadores del centro gestionan **inventario** desde su **app móvil**. Municipio se deriva de `parroquia.municipio_id` (selector encadenado en formularios).

#### `users` (campos operativos)
| Campo | Tipo | Notas |
|-------|------|-------|
| rol | ENUM | `admin`, `anfitrion`, `centro_acopio` |
| refugio_id | FK → refugios, nullable | obligatorio si `rol = anfitrion` |
| centro_acopio_id | FK → centros_acopio, nullable | obligatorio si rol = centro_acopio |

#### `inventarios`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| centro_acopio_id | FK → centros_acopio | |
| item_nombre | VARCHAR | |
| cantidad | INTEGER | |
| unidad_medida | VARCHAR | |

#### `requerimientos`
| Campo | Tipo | Notas |
|-------|------|-------|
| id | BIGSERIAL PK | |
| invitado_id | FK → invitados | idealmente jefe de familia |
| anfitrion_id | FK → users | usuario con rol `anfitrion` que registra/solicita |
| item_solicitado | VARCHAR | |
| cantidad | INTEGER | |
| estatus | ENUM | `Pendiente`, `Asignado`, `Entregado` |
| centro_acopio_id | FK → centros_acopio, nullable | asignado desde panel admin |

---

## 5. Roadmap de implementación

### Fase 1: Base de Datos y Semillas

**Objetivo:** Estructurar el esquema relacional y poblar la división político-territorial.

**Entregables:**
- [x] Migraciones: municipios, parroquias, refugios, invitados, centros_acopio, inventarios, requerimientos
- [x] Modelos con relaciones (`hasMany`, `belongsTo`, autoreferencia en invitados)
- [x] SoftDeletes en `refugios` e `invitados`
- [x] Seeders iniciales (catálogo geográfico estado **Anzoátegui** — 21 municipios, 57 parroquias)

**Prompt de referencia (Fase 1):**

```
Actúa como un desarrollador experto en Laravel 11 y arquitectura de bases de datos.
Necesito que generes las migraciones, modelos y relaciones para un sistema de
gestión de contingencias.

Debes estructurar las tablas respetando las siguientes reglas de negocio y
relaciones:
1. `municipios`: id, nombre.
2. `parroquias`: id, municipio_id (FK), nombre.
3. `refugios`: id, nombre, parroquia_id (FK), latitud (decimal 10,8), longitud
(decimal 11,8), direccion_exacta.
4. `invitados`: id, jefe_familia_id (FK autoreferenciada a invitados.id, nullable),
nombre, apellido, cedula (string, nullable), fecha_nacimiento (date), telefono
(string, nullable), foto_ingreso (string, nullable), refugio_id (FK), estatus (enum:
Activo, Egresado). Un jefe de familia tiene muchos miembros asociados. El término
"refugiado" o "damnificado" ESTÁ PROHIBIDO, usa siempre "Invitado".
5. `centros_acopio`: id, nombre, parroquia_id (FK), direccion_exacta, latitud
(decimal 10,8), longitud (decimal 11,8), contacto, activo. Mismo patrón geográfico
que refugios (municipio vía parroquia).
6. `users`: rol (admin | anfitrion | centro_acopio), refugio_id (nullable),
centro_acopio_id (nullable) según rol.
7. `inventarios`: id, centro_acopio_id (FK), item_nombre (string), cantidad
(integer), unidad_medida (string).
8. `requerimientos`: id, invitado_id (FK, idealmente jefe), anfitrion_id (FK a
users), item_solicitado (string), cantidad (integer), estatus (enum: Pendiente,
Asignado, Entregado), centro_acopio_id (FK, nullable).

Genera los archivos utilizando las convenciones estrictas de Laravel, define las
claves foráneas adecuadamente con borrado lógico (SoftDeletes) en Refugios e
Invitados, y escribe los métodos de relación correspondientes en cada modelo
(hasMany, belongsTo, etc.).
```

---

### Fase 2: Panel Administrativo (Filament v3) — Entidad 1

**Objetivo:** Gestión central de todo el ecosistema desde Filament PHP v3.

**Entregables:**
- [x] Filament Resources: Municipio, Parroquia, Refugio, Invitado, CentroAcopio, User (anfitrion y operadores de acopio)
- [x] Select dependiente Municipio → Parroquia en RefugioResource **y** CentroAcopioResource
- [x] CentroAcopioResource: dirección exacta + latitud/longitud (mapa o inputs)
- [x] InvitadoResource: búsqueda global (cédula, nombre, apellido), FileUpload foto, avatar en lista, núcleo familiar
- [x] Gestión de usuarios: asignar `anfitrion` → `refugio_id`; `centro_acopio` → `centro_acopio_id`
- [x] Labels en español; siempre "Invitado", nunca términos prohibidos
- [x] Tableros de inventario global y visor de requerimientos pendientes (widgets dashboard)

**Prompt de referencia (Fase 2):**

```
Necesito construir el panel de administración utilizando Filament PHP v3. Escribe
los Filament Resources para los modelos Municipio, Parroquia, Refugio, Invitado,
CentroAcopio y User siguiendo estos lineamientos:

1. En RefugioResource y CentroAcopioResource, el campo 'parroquia_id' debe depender
de un selector previo de 'municipio_id'. Ambos modelos requieren direccion_exacta,
latitud y longitud (georreferenciación).
2. En InvitadoResource, permite buscar globalmente por cédula, nombre o apellido.
Añade FileUpload para 'foto_ingreso' en disco 'public'. Avatar redondo en lista.
Permite asociar miembros del núcleo familiar vía jefe de familia.
3. En UserResource, asignar rol (admin | anfitrion | centro_acopio) y vincular
refugio_id o centro_acopio_id según corresponda.
4. Labels en español; siempre "Invitado". Nunca refugiado ni damnificado.
```

---

### Fase 3: App Anfitrión (Livewire + Tailwind) — Entidad 3

**Objetivo:** Interfaz móvil/responsiva para el **anfitrión** en refugio: registrar Invitados y gestionar requerimientos.

**Entregables:**
- [x] Auth `anfitrion` (login; scope automático al `refugio_id` del usuario)
- [x] Componente Livewire v3 responsivo (mobile-first)
- [x] Formulario registro Invitado: nombre, apellido, cédula, teléfono, fecha nacimiento
- [x] Refugio preasignado al anfitrión (sin selector de refugio ajeno)
- [x] Captura fotográfica nativa (`accept="image/*" capture="environment"`)
- [x] Núcleo familiar dinámico ("Agregar Familiar")
- [x] Listado de Invitados del refugio + gestión de requerimientos por Invitado
- [x] Compresión de imágenes en cliente (`browser-image-compression` vía CDN)

**Prompt de referencia (Fase 3):**

```
Crea una vista responsiva utilizando un componente de Livewire v3 y Tailwind CSS
diseñada exclusivamente para pantallas móviles de teléfonos inteligentes. Esta vista
será utilizada por el ANFITRIÓN en un refugio para registrar la llegada de un
'Invitado' y su núcleo familiar, y para gestionar los requerimientos de insumos
de sus hospedados.

Requerimientos técnicos obligatorios:
1. Formulario limpio de registro: Nombre, Apellido, Cédula, Teléfono, Fecha de
Nacimiento.
2. El anfitrión ya está vinculado a un refugio; no elige refugios ajenos. Muestra
el refugio asignado como contexto fijo del formulario.
3. Georreferenciación Automática (opcional en registro de invitado): Integra un script de JavaScript en el componente
que interactúe con la API de Geolocalización del navegador (navigator.geolocation).
Al abrir la página o presionar un botón "Fijar Ubicación", debe capturar la latitud
y longitud actuales y guardarlas automáticamente en inputs ocultos del formulario.
4. Testigo Fotográfico: Agrega un campo de captura que permita usar la cámara del
celular de forma nativa mediante el input file tradicional con el atributo
`accept="image/*" capture="environment"`, vinculándolo con Livewire para procesar la
subida temporal de la foto de ingreso.
5. Lógica para núcleo familiar: Permite presionar un botón "Agregar Familiar" para
abrir sub-campos dinámicos que guarden a los familiares vinculándolos directamente
bajo el ID del Jefe de Familia que se está registrando en el formulario principal.
6. Sección de requerimientos: el anfitrión puede registrar carencias del Invitado
(agua, sábanas, alimentos, ropa) vinculadas a su refugio.
```

---

### Fase 4: App Centros de Acopio + Consolidación — Entidad 2

**Objetivo:** App móvil para operadores de centros de acopio + emparejamiento central de requerimientos desde el panel.

**Entregables — App centros de acopio:**
- [x] Auth operador de acopio (login; scope automático al `centro_acopio_id`)
- [x] Vista de inventario del centro: listar, agregar y actualizar ítems/cantidades
- [x] Registro/alta de centro (desde panel) con municipio, parroquia, dirección y georreferenciación
- [x] Notificaciones o listado de requerimientos asignados al centro
- [x] Marcar requerimiento como `Entregado` y descontar stock

**Entregables — Panel administrativo (consolidación):**
- [x] Vista "Búsqueda Cruzada de Insumos" (requerimiento → centros con stock, ordenados por proximidad o volumen)
- [x] Asignación de requerimiento a centro de acopio → estatus `Asignado`
- [x] Alertas de stock mínimo y desabastecimiento (filtro en InventarioResource ≤5)

**Entregables — Seguridad:**
- [x] Policies: `anfitrion` (invitados y requerimientos de su refugio) · `centro_acopio` (inventario propio) · `admin` (todo)
- [x] Middleware por rol en rutas `/anfitrion` y `/acopio`

**Prompt de referencia (Fase 4):**

```
Desarrolla la lógica de negocio para Centros de Acopio, Requerimientos e Inventarios.

ENTIDAD 2 — App centros de acopio (Livewire móvil):
1. El operador del centro gestiona su inventario (agregar ítems, actualizar cantidades,
   unidad de medida). Solo ve y edita el inventario de SU centro_acopio_id.
2. Los centros de acopio se registran con municipio, parroquia (selectores encadenados),
   dirección exacta y georreferenciación (latitud/longitud vía navigator.geolocation
   o inputs del panel admin).
3. Listado de requerimientos asignados al centro; marcar como Entregado y descontar stock.

ENTIDAD 1 — Panel administrativo:
4. Vista "Búsqueda Cruzada de Insumos": cuando un Invitado requiera un ítem, mostrar
   qué Centros de Acopio tienen existencias, ordenados por proximidad geográfica
   (lat/lng del refugio vs centro) o por mayor volumen disponible.
5. Asignar requerimiento a centro → estatus 'Asignado'.

SEGURIDAD:
6. Rol `anfitrion`: solo invitados y requerimientos de su refugio.
   Rol `centro_acopio`: solo inventario de su centro.
   Rol `admin`: control global de despachos e inventarios.
```

---

### Fase 6: Logística de campo y seguimiento operativo

**Objetivo:** Mejorar la operación en campo con distancia/rutas en acopio y seguimiento de requerimientos para anfitriones.

**Entregables — App centros de acopio:**
- [x] Distancia en km del centro al refugio destino en cada entrega asignada
- [x] Entregas ordenadas por proximidad (más cercanas primero)
- [x] Enlaces «Cómo llegar» (Google Maps directions) y «Ver refugio»

**Entregables — App anfitrión:**
- [x] Página `/anfitrion/requerimientos` con seguimiento de estatus (Pendiente / Asignado / Entregado)
- [x] Filtros por estado + badge en navegación inferior
- [x] Muestra centro de acopio asignado cuando el requerimiento está en tránsito

**Entregables — Utilidades:**
- [x] `App\Support\GeoNavigation` (URLs de mapas y rutas)
- [x] Comando `visitantes:ensure-demo-users` y login admin con credenciales demo visibles en local
- [x] Tests `Fase6LogisticaTest`

---

### Fase 7: Modo offline y sincronización (campo)

**Objetivo:** Operación en zonas con conectividad limitada. Registro online prioritario; cola local solo sin internet; sincronización automática al reconectar.

**Entregables — Catálogo en caché del teléfono (IndexedDB):**
- [x] Municipios, parroquias, refugios y centros de acopio activos
- [x] Contexto del operador (refugio / centro asignado)
- [x] Inventario local del centro (operador acopio)
- [x] Unidades de medida e ítems sugeridos
- [x] Actualización del catálogo en cada recarga con conexión (`GET /api/offline/catalog`)

**Entregables — Cola offline y sync:**
- [x] Registro de Invitado + familiares + foto (anfitrión)
- [x] Creación de requerimientos (anfitrión)
- [x] Alta y ajuste de inventario (acopio)
- [x] Sincronización de ajuste de cantidad (`inventario.update_cantidad`)
- [x] Sincronización por lotes (`POST /api/offline/sync`) al volver internet
- [x] Banner de estado: sin conexión / pendientes / catálogo listo
- [x] PWA: `manifest.json`, service worker, página `/offline.html`

**Entregables — Seguridad:**
- [x] API offline solo para roles `anfitrion` y `centro_acopio` (middleware `field_operator`)
- [x] Validación server-side en `OfflineSyncService`

> **Nota:** La implementación PWA/Livewire de Fase 7 fue prototipo. La app de producción en campo es **Flutter** (Fase 8).

---

### Fase 8: App móvil Flutter (Anfitrión + Acopio)

**Objetivo:** App nativa Android/iOS con Material 3, consumiendo API Laravel Sanctum. Offline con Hive en el dispositivo.

**Entregables — Backend API (`/api/mobile`):**
- [x] Laravel Sanctum + tokens por dispositivo
- [x] `POST /login`, `POST /logout`, `GET /me`
- [x] `GET /catalog`, `POST /sync` (reutiliza servicios offline)
- [x] `GET /hogares`, `PUT /hogar-activo` (multi-hogar anfitrión)
- [x] Tests `MobileApiTest`

**Entregables — Flutter (`mobile/`):**
- [x] Proyecto Flutter con Material 3 + identidad visual Venezuela
- [x] Login unificado (detecta rol anfitrión / acopio)
- [x] Caché local Hive: catálogo + cola de sincronización
- [x] Anfitrión: registro de Invitado (+ foto comprimida) online/offline
- [x] Parentesco obligatorio en núcleo familiar
- [x] Insumos por categoría / subcategoría (`InsumoPicker`)
- [x] Acopio: alta y edición de cantidad en inventario online/offline
- [x] Cola de sync visible + botón sincronizar / actualizar caché
- [x] Requerimientos (anfitrión): listado y creación
- [x] Entregas (acopio): listado asignados + marcar entregado + distancia/ruta
- [x] Listado de Invitados del refugio
- [x] Núcleo familiar en registro
- [x] Wizard onboarding hogar + núcleo (primer registro)
- [x] Multi-hogar: selector hogar activo + «Registrar otro hogar y núcleo»
- [x] Guía de build Android/iOS (`mobile/README.md`)
- [ ] Publicación en Play Store / App Store (firmado release en producción)

---

### Mejoras post-Fase 8 (jul 2026)

- [x] Catálogo `InsumoCatalog` (8 categorías humanitarias + subcategorías)
- [x] Migración `categoria` / `subcategoria` en inventarios y requerimientos
- [x] Fix sync `inventario.update_cantidad` en backend
- [x] Compresión fotos Flutter (`flutter_image_compress`, ~0.8 MB)
- [x] `API_BASE_URL` configurable vía `--dart-define` para builds de producción
- [x] Hogares Solidarios (reemplazo refugios en app anfitrión) + procedencia INE
- [x] Vínculo hogar por wizard (`hogar_vinculado_en`, `anfitrion_user_id`) — evita hogar demo ajeno
- [x] Fix pantalla en blanco wizard «Registrar núcleo» (layout Flutter)
- [x] Widget dashboard top hogares a ancho completo (`columnSpan = full`)
- [x] **Multi-hogar anfitrión:** varios hogares/núcleos, hogar activo, API + Flutter + sync offline (`eb9ca11`)

---

## 6. Recomendaciones críticas (entorno Venezuela)

### 6.1 Sincronización geográfica prefabricada

El catálogo de estados, municipios y parroquias de Venezuela es estático y oficial (INE). No transcribir a mano.

**Acción:** Seeder con la estructura oficial del estado **Anzoátegui** (21 municipios, parroquias) en `database/seeders/data/anzoategui_geografia.php`. La operación está **circunscrita a este estado** — no se cargan otros estados.

### 6.2 Optimización de imágenes en campo

Las redes móviles en zonas de contingencia suelen ser lentas o inestables. Instalar compresión del lado del cliente (`browser-image-compression` vía CDN en la vista Livewire) antes de enviar la foto al servidor, para evitar saturar el ancho de banda con imágenes de 5–10 MB.

### 6.3 Búsqueda unificada para identificación

En Filament, usar `getGloballySearchableAttributes()` en `InvitadoResource` indexando `cedula`, `nombre` y `apellido`. Así, con solo escribir un apellido en la barra superior se localiza al instante la ubicación, estatus y fotografía de ingreso.

---

## 7. Roles, entidades y permisos

| Entidad | Rol (`users.rol`) | Interfaz | Permisos |
|---------|-------------------|----------|----------|
| **Panel administrativo** | `admin` | Filament | Control total: catálogo geográfico, refugios, centros de acopio, usuarios, invitados, inventarios globales, asignación de requerimientos, reportes |
| **Centro de acopio** | `centro_acopio` | App Livewire | Gestionar inventario de **su** centro; ver requerimientos asignados; marcar entregas. Centro registrado con municipio, parroquia, dirección y georreferenciación |
| **Anfitrión** | `anfitrion` | App Flutter | Registrar Invitados y núcleo familiar en **su hogar activo** (puede tener varios hogares); crear y dar seguimiento a requerimientos de sus hospedados |

---

## 8. Notas de avance

| Fecha | Fase | Notas |
|-------|------|-------|
| 2026-07-06 | 0 | Prompt maestro creado a partir de `plan_desarrollo_cursor_laravel.pdf`. Proyecto `visitantes` inicializado. |
| 2026-07-06 | 3 | App anfitrión Livewire en `/anfitrion`. Login, registro invitado+familia+foto, listado, requerimientos. Demo: `anfitrion@visitantes.test` / `password`. |
| 2026-07-06 | 3–4 | Design system **Material 3** (Roboto, Material Symbols, tokens Tailwind, componentes `x-m3.*`). Apps móviles con inputs con icono, nav inferior y cards M3. |
| 2026-07-06 | 4 | App acopio Livewire en `/acopio`. Inventario, entregas, búsqueda cruzada Filament. Demo: `acopio@visitantes.test` / `password`. |
| 2026-07-06 | 4 | Policies (`Invitado`, `Requerimiento`, `Inventario`) + landing M3 en `/`. |
| 2026-07-06 | — | **Ámbito territorial:** operación circunscrita a **Anzoátegui** (21 municipios). Demo en Puerto La Cruz, Barcelona, El Tigre y Lechería. |
| 2026-07-06 | 5 | Branding territorial Filament + apps M3. Demo multi-ciudad y credenciales adicionales de anfitrión/acopio. |
| 2026-07-06 | 5 | GPS en formularios Filament (refugios/centros). Página **Reportes** con exportación CSV. |
| 2026-07-06 | 5 | **Mapa operativo** Leaflet (refugios + centros). App acopio: badge y auto-refresh de entregas. |
| 2026-07-06 | 6 | App acopio: distancia km, orden por proximidad, enlaces Google Maps. App anfitrión: seguimiento requerimientos. |
| 2026-07-06 | 7 | **Modo offline:** catálogo IndexedDB, cola local, sync API, PWA, banner de conexión. |
| 2026-07-06 | 8 | **App Flutter** en `mobile/` — Sanctum API, login por rol, registro Invitado e inventario con cola offline Hive. |
| 2026-07-06 | — | Las apps Livewire `/anfitrion` y `/acopio` quedan como prototipo; producción de campo = Flutter. |
| 2026-07-06 | — | Fix login admin (BD vacía tras tests, PHPUnit en `:memory:`). Comando `visitantes:ensure-demo-users`. |
| 2026-07-07 | 2 | Filament Invitados: fotos S3 vía URL autenticada (`/invitados/{id}/foto`); familiares heredan foto del jefe de familia. |
| 2026-07-08 | — | **Bitácora de auditoría** (`activity_logs`): Invitados, requerimientos, inventario, usuarios; panel admin → Bitácora (solo lectura). |
| 2026-07-08 | 5 | **Demanda consolidada por refugio** en Filament: agrupa requerimientos por refugio+ítem y asignación en lote al centro de acopio. |
| 2026-07-12 | 8 | Hogares Solidarios + wizard onboarding (migraciones `100007`/`100008`). Vínculo por wizard; invalidación hogar demo ajeno (`fc3984b`, `a545ff4`). |
| 2026-07-12 | 8 | Fix pantalla en blanco wizard Flutter «Registrar núcleo» (`a8b8041`). Widget top hogares ancho completo en Filament. |
| 2026-07-12 | 8 | **Multi-hogar anfitrión:** `GET /hogares`, `PUT /hogar-activo`, selector en app, registrar otro hogar+núcleo, tests (`eb9ca11`). Deploy Railway OK. |

---

## 9. Convenciones de código

- Convenciones estrictas de Laravel (PascalCase modelos, snake_case tablas/columnas)
- `declare(strict_types=1)` en PHP nuevo cuando aplique
- Form Requests para validación; Policies para autorización
- SoftDeletes solo en `refugios` e `invitados`
- Fotos en `storage/app/public/invitados/{id}/` o estructura similar
- Todo el UI en **español**
- **Nunca** usar "refugiado", "damnificado" ni "afectado" en código ni UI
