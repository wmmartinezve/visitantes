import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class CatalogService extends ChangeNotifier {
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

  bool get isReady {
    final catalog = cachedCatalog;
    if (catalog == null) return false;
    final estados = catalog['estados'] as List?;
    final municipios = catalog['municipios'] as List?;
    return (estados?.isNotEmpty ?? false) && (municipios?.isNotEmpty ?? false);
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

      // Siempre persistir: el bloque `operador` depende del usuario y no entra en `version`.
      await LocalDb.catalog.put('current', catalog);
      await LocalDb.meta.put('catalog_version', version);
      await LocalDb.meta.put('catalog_updated_at', catalog['generated_at']);

      notifyListeners();
      return catalog;
    } catch (_) {
      return cachedCatalog;
    }
  }

  Future<void> clear() async {
    await LocalDb.catalog.delete('current');
    await LocalDb.meta.delete('catalog_version');
    await LocalDb.meta.delete('catalog_updated_at');
    notifyListeners();
  }

  /// Sincroniza flags del operador en caché tras crear hogar o actualizar sesión.
  Future<void> patchOperadorForUser(MobileUser user) async {
    final catalog = cachedCatalog;
    if (catalog == null) return;

    final sinHogar = user.refugioId == null;
    final operador = Map<String, dynamic>.from(catalog['operador'] as Map? ?? {});
    operador['hogar_solidario_id'] = user.refugioId;
    operador['requiere_registro_hogar'] = sinHogar;
    if (sinHogar) {
      operador['tiene_nucleo_familiar'] = false;
      operador.remove('hogar_solidario');
      operador.remove('refugio');
    } else if (user.refugioNombre != null) {
      operador['hogar_solidario'] = {
        'id': user.refugioId,
        'codigo': user.refugioNombre,
        'nombre': user.refugioNombre,
      };
      operador['refugio'] = operador['hogar_solidario'];
    }

    final updated = Map<String, dynamic>.from(catalog)..['operador'] = operador;
    await LocalDb.catalog.put('current', updated);
    notifyListeners();
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
    notifyListeners();
  }
}
