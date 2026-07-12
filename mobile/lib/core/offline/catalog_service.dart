import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class CatalogService {
  CatalogService({ApiClient? apiClient, Connectivity? connectivity})
      : _api = apiClient ?? ApiClient(),
        _connectivity = connectivity ?? Connectivity();

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

  Future<Map<String, dynamic>?> refresh({bool force = false}) async {
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
      final lastVersion = LocalDb.meta.get('catalog_version') as String?;

      if (force || version != lastVersion) {
        await LocalDb.catalog.put('current', catalog);
        await LocalDb.meta.put('catalog_version', version);
        await LocalDb.meta.put('catalog_updated_at', catalog['generated_at']);
      }

      return catalog;
    } catch (_) {
      return cachedCatalog;
    }
  }

  int get municipiosCount => (cachedCatalog?['municipios'] as List?)?.length ?? 0;
  int get parroquiasCount => (cachedCatalog?['parroquias'] as List?)?.length ?? 0;
  int get centrosCount => (cachedCatalog?['centros_acopio'] as List?)?.length ?? 0;

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
  }
}
