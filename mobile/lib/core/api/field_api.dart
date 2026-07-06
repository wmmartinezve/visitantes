import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class FieldApi {
  FieldApi({ApiClient? apiClient, CatalogService? catalogService})
      : _api = apiClient ?? ApiClient(),
        _catalog = catalogService ?? CatalogService();

  final ApiClient _api;
  final CatalogService _catalog;

  List<T> _parseList<T>(dynamic raw, T Function(Map<String, dynamic>) fromJson) {
    if (raw is! List) return [];
    return raw.map((e) => fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<InvitadoModel>> fetchInvitados({String query = ''}) async {
    if (!await _catalog.isOnline) {
      return _cachedInvitados();
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/invitados',
        queryParameters: query.isEmpty ? null : {'q': query},
      );
      final list = _parseList(response.data?['data'], InvitadoModel.fromJson);
      await LocalDb.meta.put('invitados_cache', list.map((e) => _invitadoToMap(e)).toList());
      return list;
    } catch (_) {
      return _cachedInvitados();
    }
  }

  Future<InvitadoModel?> fetchInvitado(int id) async {
    if (!await _catalog.isOnline) {
      return _cachedInvitados().where((i) => i.id == id).firstOrNull;
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/invitados/$id');
      final data = response.data?['data'];
      if (data is! Map) return null;
      return InvitadoModel.fromJson(Map<String, dynamic>.from(data));
    } catch (_) {
      return _cachedInvitados().where((i) => i.id == id).firstOrNull;
    }
  }

  Future<List<RequerimientoModel>> fetchRequerimientos({String estatus = 'todos'}) async {
    if (!await _catalog.isOnline) {
      return _cachedRequerimientos();
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/requerimientos',
        queryParameters: estatus == 'todos' ? null : {'estatus': estatus},
      );
      final list = _parseList(response.data?['data'], RequerimientoModel.fromJson);
      await LocalDb.meta.put('requerimientos_cache', list.map(_requerimientoToMap).toList());
      return list;
    } catch (_) {
      return _cachedRequerimientos();
    }
  }

  Future<List<RequerimientoModel>> fetchEntregas() async {
    if (!await _catalog.isOnline) {
      return _cachedEntregas();
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/entregas');
      final list = _parseList(response.data?['data'], RequerimientoModel.fromJson);
      await LocalDb.meta.put('entregas_cache', list.map(_requerimientoToMap).toList());
      return list;
    } catch (_) {
      return _cachedEntregas();
    }
  }

  Future<void> createRequerimientoOnline({
    required int invitadoId,
    required String categoria,
    required String subcategoria,
    required int cantidad,
  }) async {
    await _api.dio.post<void>('/requerimientos', data: {
      'invitado_id': invitadoId,
      'categoria': categoria,
      'subcategoria': subcategoria,
      'cantidad': cantidad,
    });
  }

  Future<void> marcarEntregadoOnline(int requerimientoId) async {
    await _api.dio.post<void>('/entregas/$requerimientoId/entregar');
  }

  List<InvitadoModel> _cachedInvitados() {
    final raw = LocalDb.meta.get('invitados_cache');
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => InvitadoModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  List<RequerimientoModel> _cachedRequerimientos() {
    final raw = LocalDb.meta.get('requerimientos_cache');
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => RequerimientoModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  List<RequerimientoModel> _cachedEntregas() {
    final raw = LocalDb.meta.get('entregas_cache');
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => RequerimientoModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Map<String, dynamic> _invitadoToMap(InvitadoModel i) => {
        'id': i.id,
        'nombre_completo': i.nombreCompleto,
        'cedula': i.cedula,
        'telefono': i.telefono,
        'estatus_label': i.estatusLabel,
      };

  Map<String, dynamic> _requerimientoToMap(RequerimientoModel r) => {
        'id': r.id,
        'item_solicitado': r.itemSolicitado,
        'cantidad': r.cantidad,
        'estatus': r.estatus,
        'estatus_label': r.estatusLabel,
        'invitado_id': r.invitadoId,
        'invitado_nombre': r.invitadoNombre,
        'centro_acopio_nombre': r.centroAcopioNombre,
        'refugio_nombre': r.refugioNombre,
        'refugio_direccion': r.refugioDireccion,
        'distancia_km': r.distanciaKm,
        'ruta_url': r.rutaUrl,
        'refugio_url': r.refugioUrl,
      };
}

extension _FirstOrNull<E> on Iterable<E> {
  E? get firstOrNull {
    final iterator = this.iterator;
    if (iterator.moveNext()) return iterator.current;
    return null;
  }
}
