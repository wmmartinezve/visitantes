import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class CatalogService extends ChangeNotifier {
  CatalogService({ApiClient? apiClient, Connectivity? connectivity})
      : _api = apiClient ?? ApiClient(),
        _connectivity = connectivity ?? Connectivity();

  static const Duration cacheTtl = Duration(hours: 24);

  final ApiClient _api;
  final Connectivity _connectivity;

  Future<bool> get isOnline async {
    final result = await _connectivity.checkConnectivity();
    return !result.contains(ConnectivityResult.none);
  }

  Map<String, dynamic>? get cachedCatalog {
    final raw = LocalDb.catalog.get('current');
    if (raw is Map) {
      return Map<String, dynamic>.from(raw);
    }
    return null;
  }

  DateTime? get cacheFetchedAt {
    final explicit = LocalDb.meta.get('catalog_cached_at');
    if (explicit is String) {
      final parsed = DateTime.tryParse(explicit);
      if (parsed != null) return parsed.toLocal();
    }

    final generatedAt = cachedCatalog?['generated_at'];
    if (generatedAt is String) {
      return DateTime.tryParse(generatedAt)?.toLocal();
    }

    return null;
  }

  bool get isCacheExpired {
    final fetchedAt = cacheFetchedAt;
    if (fetchedAt == null) return true;
    return DateTime.now().difference(fetchedAt) > cacheTtl;
  }

  Duration? get cacheTimeRemaining {
    final fetchedAt = cacheFetchedAt;
    if (fetchedAt == null) return null;
    final remaining = cacheTtl - DateTime.now().difference(fetchedAt);
    if (remaining.isNegative) return Duration.zero;
    return remaining;
  }

  bool get hasRegistrationCatalogData {
    final catalog = cachedCatalog;
    if (catalog == null) return false;

    bool hasList(String key) {
      final raw = catalog[key];
      return raw is List && raw.isNotEmpty;
    }

    return hasList('estados') &&
        hasList('municipios') &&
        hasList('parroquias') &&
        hasList('parentescos') &&
        hasList('tipos_vivienda') &&
        hasList('tipos_anfitrion') &&
        hasList('situaciones_jefe') &&
        hasList('condiciones');
  }

  /// Catálogo listo para formularios (online u offline con caché local).
  bool get isReady => hasRegistrationCatalogData;

  bool get canWorkOffline => hasRegistrationCatalogData && !isCacheExpired;

  /// Garantiza catálogo en Hive: usa caché válida (<24 h) o descarga si hay red.
  Future<Map<String, dynamic>?> ensureCached({bool force = false}) async {
    final cached = cachedCatalog;

    if (!force && cached != null && !isCacheExpired && hasRegistrationCatalogData) {
      return cached;
    }

    if (await isOnline) {
      final fresh = await refresh(force: true);
      if (fresh != null) return fresh;
    }

    return cached;
  }

  Future<Map<String, dynamic>?> refresh({bool force = false}) async {
    if (!force && cachedCatalog != null && !isCacheExpired && hasRegistrationCatalogData) {
      return cachedCatalog;
    }

    if (!await isOnline) {
      return cachedCatalog;
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/catalog');
      final catalog = response.data;
      if (catalog == null) {
        return cachedCatalog;
      }

      final version = catalog['version'] as String?;

      await _persistCatalog(catalog, version: version);
      notifyListeners();
      return catalog;
    } catch (_) {
      return cachedCatalog;
    }
  }

  Future<void> _persistCatalog(Map<String, dynamic> catalog, {String? version}) async {
    await LocalDb.catalog.put('current', catalog);
    await LocalDb.meta.put('catalog_version', version);
    await LocalDb.meta.put('catalog_updated_at', catalog['generated_at']);
    await LocalDb.meta.put('catalog_cached_at', DateTime.now().toUtc().toIso8601String());
  }

  Future<void> clear() async {
    await LocalDb.catalog.delete('current');
    await LocalDb.meta.delete('catalog_version');
    await LocalDb.meta.delete('catalog_updated_at');
    await LocalDb.meta.delete('catalog_cached_at');
    notifyListeners();
  }

  /// Alinea el bloque `operador` del catálogo con el usuario devuelto por la API.
  Future<void> syncOperadorFromUser(MobileUser user) async {
    final catalog = cachedCatalog;
    if (catalog == null) return;

    final operador = Map<String, dynamic>.from(catalog['operador'] as Map? ?? {});
    operador['hogar_solidario_id'] = user.debeRegistrarHogar ? null : user.refugioId;
    operador['requiere_registro_hogar'] = user.requiereRegistroHogar;
    operador['puede_registrar_otro_hogar'] = user.puedeRegistrarOtroHogar;
    operador['hogares_count'] = user.hogaresCount;
    operador['hogares'] = user.hogares
        .map((h) => {
              'id': h.id,
              'codigo': h.codigo,
              'nombre': h.codigo,
              'direccion_exacta': h.direccionExacta,
              'tiene_nucleo_familiar': h.tieneNucleoFamiliar,
            })
        .toList();
    operador['tiene_nucleo_familiar'] = user.tieneNucleoFamiliar;

    if (user.debeRegistrarHogar) {
      operador.remove('hogar_solidario');
      operador.remove('refugio');
    } else if (user.refugioId != null) {
      final hogar = {
        'id': user.refugioId,
        'codigo': user.refugioNombre,
        'nombre': user.refugioNombre,
      };
      operador['hogar_solidario'] = hogar;
      operador['refugio'] = hogar;
    }

    final updated = Map<String, dynamic>.from(catalog)..['operador'] = operador;
    await LocalDb.catalog.put('current', updated);
    notifyListeners();
  }

  @Deprecated('Use syncOperadorFromUser')
  Future<void> patchOperadorForUser(MobileUser user) => syncOperadorFromUser(user);

  int get municipiosCount => (cachedCatalog?['municipios'] as List?)?.length ?? 0;
  int get parroquiasCount => (cachedCatalog?['parroquias'] as List?)?.length ?? 0;
  int get centrosCount => (cachedCatalog?['centros_acopio'] as List?)?.length ?? 0;

  String get offlineCacheSummary {
    if (!hasRegistrationCatalogData) {
      return 'Sin caché de catálogo';
    }

    final remaining = cacheTimeRemaining;
    if (isCacheExpired) {
      return 'Caché expirada · $municipiosCount mun. · conecte para actualizar';
    }

    if (remaining == null) {
      return 'Caché offline · $municipiosCount mun. · $parroquiasCount parr.';
    }

    final hours = remaining.inHours;
    final minutes = remaining.inMinutes.remainder(60);
    final ttlLabel = hours > 0 ? '${hours}h ${minutes}m' : '${minutes}m';
    return 'Caché offline · $municipiosCount mun. · válida $ttlLabel';
  }

  bool get tieneNucleoFamiliarEnHogar {
    final operador = cachedCatalog?['operador'];
    if (operador is Map) {
      return operador['tiene_nucleo_familiar'] == true;
    }
    return false;
  }

  bool get requiereRegistroHogar {
    final operador = cachedCatalog?['operador'];
    if (operador is Map) {
      return operador['requiere_registro_hogar'] == true;
    }
    return false;
  }

  bool get puedeRegistrarOtroHogar {
    final operador = cachedCatalog?['operador'];
    if (operador is Map) {
      return operador['puede_registrar_otro_hogar'] == true;
    }
    return false;
  }

  List<Map<String, dynamic>> get hogaresOperador {
    final operador = cachedCatalog?['operador'];
    if (operador is! Map) return [];
    final raw = operador['hogares'] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  /// Actualiza cantidad de un ítem en la caché local (p. ej. tras editar offline).
  Future<void> patchInventarioLocalCantidad(int inventarioId, int cantidad) async {
    final catalog = cachedCatalog;
    if (catalog == null) return;

    final raw = catalog['inventario_local'] as List<dynamic>? ?? [];
    final list = raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    final idx = list.indexWhere((e) => e['id'] == inventarioId);
    if (idx < 0) return;

    list[idx] = {...list[idx], 'cantidad': cantidad};
    final updated = Map<String, dynamic>.from(catalog)..['inventario_local'] = list;
    await LocalDb.catalog.put('current', updated);
    notifyListeners();
  }
}
