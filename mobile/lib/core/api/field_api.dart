import 'package:dio/dio.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
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
    final trimmedQuery = query.trim();

    if (!await _catalog.isOnline) {
      return _filterInvitados(_cachedInvitados(), trimmedQuery);
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>(
        '/invitados',
        queryParameters: trimmedQuery.isEmpty ? null : {'q': trimmedQuery},
      );
      final list = _parseList(response.data?['data'], InvitadoModel.fromJson);
      final unique = <int, InvitadoModel>{};
      for (final invitado in list) {
        unique[invitado.id] = invitado;
      }
      final deduped = unique.values.toList();
      // Solo actualizar caché con el listado completo; no sobrescribir con resultados filtrados.
      if (trimmedQuery.isEmpty) {
        await LocalDb.meta.put('invitados_cache', deduped.map((e) => _invitadoToMap(e)).toList());
      }
      return deduped;
    } catch (_) {
      return _filterInvitados(_cachedInvitados(), trimmedQuery);
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

  Future<HogarSolidarioDetail?> fetchHogarDetail(int id) async {
    if (!await _catalog.isOnline) {
      return null;
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/hogares/$id');
      final data = response.data?['data'];
      if (data is! Map) return null;
      return HogarSolidarioDetail.fromJson(Map<String, dynamic>.from(data));
    } catch (_) {
      return null;
    }
  }

  Future<HogaresResumen> fetchHogaresResumen() async {
    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/hogares');
      final data = response.data ?? {};
      final raw = data['data'] as List<dynamic>? ?? [];
      final hogares = raw
          .map((e) => HogarSolidarioInfo.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();

      int? parseInt(dynamic value) {
        if (value == null) return null;
        if (value is int) return value;
        if (value is num) return value.toInt();
        if (value is String && value.isNotEmpty) return int.tryParse(value);
        return null;
      }

      return HogaresResumen(
        hogares: hogares,
        hogaresCount: parseInt(data['hogares_count']) ?? hogares.length,
        invitadosCount: parseInt(data['invitados_count']) ?? 0,
        hogarActivoId: parseInt(data['hogar_activo_id']),
      );
    } catch (_) {
      return const HogaresResumen(hogares: [], hogaresCount: 0, invitadosCount: 0);
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

  Future<RegisterInvitadoResult> registerInvitadoOnline(Map<String, dynamic> payload) async {
    final response = await _api.dio.post<Map<String, dynamic>>(
      '/invitados',
      data: payload,
      options: Options(
        sendTimeout: const Duration(seconds: 120),
        receiveTimeout: const Duration(seconds: 60),
      ),
    );
    final body = response.data;
    final data = body?['data'];
    if (data is! Map) {
      throw StateError('Respuesta inválida del servidor');
    }
    final invitado = InvitadoModel.fromJson(Map<String, dynamic>.from(data));
    await _prependInvitadoToCache(invitado);

    MobileUser? updatedUser;
    if (body?['user'] is Map) {
      updatedUser = MobileUser.fromJson(Map<String, dynamic>.from(body!['user'] as Map));
    }

    return RegisterInvitadoResult(invitado: invitado, updatedUser: updatedUser);
  }

  Future<void> _prependInvitadoToCache(InvitadoModel invitado) async {
    final cached = _cachedInvitados();
    final updated = [invitado, ...cached.where((i) => i.id != invitado.id)];
    await LocalDb.meta.put('invitados_cache', updated.map(_invitadoToMap).toList());
  }

  Future<InvitadoModel> uploadInvitadoFoto(int invitadoId, Map<String, dynamic> payload) async {
    final response = await _api.dio.post<Map<String, dynamic>>(
      '/invitados/$invitadoId/foto',
      data: payload,
      options: Options(
        sendTimeout: const Duration(seconds: 120),
        receiveTimeout: const Duration(seconds: 60),
      ),
    );
    final data = response.data?['data'];
    if (data is! Map) {
      throw StateError('Respuesta inválida del servidor');
    }
    final invitado = InvitadoModel.fromJson(Map<String, dynamic>.from(data));
    await _prependInvitadoToCache(invitado);
    return invitado;
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

  Future<CentroAcopioInfo> updateCentroGeolocalizacion({
    required double latitud,
    required double longitud,
    String? direccionExacta,
  }) async {
    final response = await _api.dio.put<Map<String, dynamic>>(
      '/centro/geolocalizacion',
      data: {
        'latitud': latitud,
        'longitud': longitud,
        if (direccionExacta != null && direccionExacta.trim().isNotEmpty)
          'direccion_exacta': direccionExacta.trim(),
      },
    );

    final data = response.data?['data'];
    if (data is! Map) {
      throw StateError('Respuesta inválida del servidor');
    }

    return CentroAcopioInfo.fromJson(Map<String, dynamic>.from(data));
  }

  List<InvitadoModel> _cachedInvitados() {
    final raw = LocalDb.meta.get('invitados_cache');
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => InvitadoModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  List<InvitadoModel> _filterInvitados(List<InvitadoModel> list, String query) {
    if (query.isEmpty) return list;

    final q = query.toLowerCase();

    return list.where((invitado) {
      if (invitado.nombreCompleto.toLowerCase().contains(q)) return true;
      if (invitado.cedula?.toLowerCase().contains(q) ?? false) return true;
      if (invitado.parentesco?.toLowerCase().contains(q) ?? false) return true;
      if (invitado.telefono?.toLowerCase().contains(q) ?? false) return true;
      if (invitado.hogarCodigo?.toLowerCase().contains(q) ?? false) return true;

      return false;
    }).toList();
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
        'fecha_nacimiento': i.fechaNacimiento,
        'edad': i.edad,
        'registrado_el': i.registradoEl,
        'foto_url': i.fotoUrl,
        'es_jefe_familia': i.esJefeFamilia,
        'parentesco': i.parentesco,
        'detail_invitado_id': i.detailInvitadoId,
        'hogar_solidario_id': i.hogarSolidarioId,
        'hogar_codigo': i.hogarCodigo,
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
        'refugio_latitud': r.refugioLatitud,
        'refugio_longitud': r.refugioLongitud,
        'centro_latitud': r.centroLatitud,
        'centro_longitud': r.centroLongitud,
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

class RegisterInvitadoResult {
  const RegisterInvitadoResult({required this.invitado, this.updatedUser});

  final InvitadoModel invitado;
  final MobileUser? updatedUser;
}

class HogaresResumen {
  const HogaresResumen({
    required this.hogares,
    required this.hogaresCount,
    required this.invitadosCount,
    this.hogarActivoId,
  });

  final List<HogarSolidarioInfo> hogares;
  final int hogaresCount;
  final int invitadosCount;
  final int? hogarActivoId;
}
