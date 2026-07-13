import 'package:dio/dio.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
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

  Future<List<InvitadoModel>> fetchInvitados({String query = '', bool forceRefresh = false}) async {
    final trimmedQuery = query.trim();

    if (!await _catalog.isOnline) {
      return _filterInvitados(_cachedInvitados(), trimmedQuery);
    }

    if (forceRefresh && trimmedQuery.isEmpty) {
      await LocalDb.meta.delete('invitados_cache');
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
      if (trimmedQuery.isEmpty) {
        await LocalDb.meta.put('invitados_cache', deduped.map((e) => _invitadoToMap(e)).toList());
      }
      return deduped;
    } catch (_) {
      return _filterInvitados(_cachedInvitados(), trimmedQuery);
    }
  }

  /// Actualiza cachés locales de hogares e Invitados del anfitrión.
  Future<bool> refreshAnfitrionCaches() async {
    if (!await _catalog.isOnline) {
      return false;
    }

    try {
      await LocalDb.meta.delete('invitados_cache');
      await fetchInvitados(forceRefresh: true);
      await fetchHogaresResumen();
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<HogarSolidarioDetail?> fetchHogarDetail(int id, {HogarSolidarioInfo? preview}) async {
    if (await _catalog.isOnline) {
      try {
        final response = await _api.dio.get<Map<String, dynamic>>('/hogares/$id');
        final data = response.data?['data'];
        if (data is Map) {
          final detail = HogarSolidarioDetail.fromJson(Map<String, dynamic>.from(data));
          await _cacheHogarDetail(detail);
          return detail;
        }
      } on DioException catch (e) {
        if (e.response?.statusCode == 404) {
          return _buildHogarDetailFallback(id, preview: preview, partial: true);
        }
      } catch (_) {}
    }

    final cached = _cachedHogarDetail(id);
    if (cached != null) {
      return cached;
    }

    return _buildHogarDetailFallback(id, preview: preview);
  }

  Future<void> _cacheHogarDetail(HogarSolidarioDetail detail) async {
    final raw = LocalDb.meta.get('hogares_detail_cache');
    final map = raw is Map ? Map<String, dynamic>.from(raw) : <String, dynamic>{};
    map['${detail.id}'] = _hogarDetailToMap(detail);
    await LocalDb.meta.put('hogares_detail_cache', map);
  }

  HogarSolidarioDetail? _cachedHogarDetail(int id) {
    final raw = LocalDb.meta.get('hogares_detail_cache');
    if (raw is! Map) return null;
    final entry = raw['$id'];
    if (entry is! Map) return null;
    return HogarSolidarioDetail.fromJson(Map<String, dynamic>.from(entry));
  }

  HogarSolidarioDetail? _buildHogarDetailFallback(
    int id, {
    HogarSolidarioInfo? preview,
    bool partial = false,
  }) {
    final hogarPreview = preview ?? _cachedHogares().where((h) => h.id == id).firstOrNull;
    final invitados = _cachedInvitados().where((i) => i.hogarSolidarioId == id).toList();
    if (hogarPreview == null && invitados.isEmpty) {
      return null;
    }

    final jefe = invitados.where((i) => i.esJefeFamilia).firstOrNull;

    return HogarSolidarioDetail(
      id: id,
      codigo: hogarPreview?.codigo ?? 'Hogar',
      direccionExacta: hogarPreview?.direccionExacta,
      tieneNucleoFamiliar: hogarPreview?.tieneNucleoFamiliar ?? jefe != null,
      invitadosCount: hogarPreview?.invitadosCount ?? invitados.length,
      jefeFamiliar: jefe,
      invitados: invitados,
      partial: partial,
    );
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

      await LocalDb.meta.put(
        'hogares_cache',
        hogares
            .map((h) => {
                  'id': h.id,
                  'codigo': h.codigo,
                  'direccion_exacta': h.direccionExacta,
                  'tiene_nucleo_familiar': h.tieneNucleoFamiliar,
                  'invitados_count': h.invitadosCount,
                })
            .toList(),
      );
      await LocalDb.meta.put('hogares_invitados_count', parseInt(data['invitados_count']) ?? 0);

      return HogaresResumen(
        hogares: hogares,
        hogaresCount: parseInt(data['hogares_count']) ?? hogares.length,
        invitadosCount: parseInt(data['invitados_count']) ?? 0,
        hogarActivoId: parseInt(data['hogar_activo_id']),
      );
    } catch (_) {
      return HogaresResumen(
        hogares: _cachedHogares(),
        hogaresCount: _cachedHogares().length,
        invitadosCount: _cachedInvitadosCount(),
      );
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

  Future<void> _replaceInvitadoInCache(InvitadoModel invitado) async {
    final cached = _cachedInvitados();
    final idx = cached.indexWhere((i) => i.id == invitado.id);
    if (idx >= 0) {
      cached[idx] = invitado;
    } else {
      cached.insert(0, invitado);
    }
    await LocalDb.meta.put('invitados_cache', cached.map(_invitadoToMap).toList());
    await _patchHogarDetailInvitado(invitado);
  }

  Future<InvitadoModel> _patchInvitadoMencionesInCache(int invitadoId, Map<String, dynamic> payload) async {
    final catalog = _catalog.mencionesCatalogo;
    final ayudas = (payload['menciones_ayudas'] as List?)?.whereType<String>().toList() ?? [];
    final salud = (payload['menciones_salud'] as List?)?.whereType<String>().toList() ?? [];
    final tramites = (payload['menciones_tramites'] as List?)?.whereType<String>().toList() ?? [];
    final nota = payload['menciones_nota'] as String?;

    InvitadoMencionesLabels? labels;
    if (catalog != null) {
      labels = InvitadoMencionesLabels(
        ayudas: catalog.labelsFor('ayudas', ayudas),
        salud: catalog.labelsFor('salud', salud),
        tramites: catalog.labelsFor('tramites', tramites),
        nota: nota,
      );
    }

    final cached = _cachedInvitados();
    final idx = cached.indexWhere((i) => i.id == invitadoId);
    final existing = idx >= 0 ? cached[idx] : _findInvitadoInCaches(invitadoId);

    late InvitadoModel updated;
    if (existing != null) {
      updated = existing.copyWithMenciones(
        ayudas: ayudas,
        salud: salud,
        tramites: tramites,
        nota: nota,
        labels: labels,
      );
      if (idx >= 0) {
        cached[idx] = updated;
      } else {
        cached.insert(0, updated);
      }
      await LocalDb.meta.put('invitados_cache', cached.map(_invitadoToMap).toList());
    } else {
      updated = InvitadoModel(
        id: invitadoId,
        nombreCompleto: '',
        mencionesAyudas: ayudas,
        mencionesSalud: salud,
        mencionesTramites: tramites,
        mencionesNota: nota,
        mencionesLabels: labels,
      );
    }

    await _patchHogarDetailInvitado(updated);
    return updated;
  }

  InvitadoModel? _findInvitadoInCaches(int invitadoId) {
    final fromList = _cachedInvitados().where((i) => i.id == invitadoId).firstOrNull;
    if (fromList != null) return fromList;

    final raw = LocalDb.meta.get('hogares_detail_cache');
    if (raw is! Map) return null;

    for (final entry in raw.values) {
      if (entry is! Map) continue;
      final detail = Map<String, dynamic>.from(entry);

      final jefeRaw = detail['jefe_familiar'];
      if (jefeRaw is Map && jefeRaw['id'] == invitadoId) {
        return InvitadoModel.fromJson(Map<String, dynamic>.from(jefeRaw));
      }

      final invitadosRaw = detail['invitados'];
      if (invitadosRaw is List) {
        for (final inv in invitadosRaw) {
          if (inv is Map && inv['id'] == invitadoId) {
            return InvitadoModel.fromJson(Map<String, dynamic>.from(inv));
          }
        }
      }
    }

    return null;
  }

  Future<void> _patchHogarDetailInvitado(InvitadoModel invitado) async {
    final raw = LocalDb.meta.get('hogares_detail_cache');
    if (raw is! Map) return;

    final map = Map<String, dynamic>.from(raw);
    var anyChanged = false;

    for (final entry in map.entries.toList()) {
      final detailRaw = entry.value;
      if (detailRaw is! Map) continue;
      final detail = Map<String, dynamic>.from(detailRaw);
      var entryChanged = false;

      final jefeRaw = detail['jefe_familiar'];
      if (jefeRaw is Map && jefeRaw['id'] == invitado.id) {
        detail['jefe_familiar'] = _invitadoToMap(invitado);
        entryChanged = true;
      }

      final invitadosRaw = detail['invitados'];
      if (invitadosRaw is List) {
        final invitados = invitadosRaw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
        final invIdx = invitados.indexWhere((e) => e['id'] == invitado.id);
        if (invIdx >= 0) {
          invitados[invIdx] = _invitadoToMap(invitado);
          detail['invitados'] = invitados;
          entryChanged = true;
        }
      }

      if (entryChanged) {
        map[entry.key] = detail;
        anyChanged = true;
      }
    }

    if (anyChanged) {
      await LocalDb.meta.put('hogares_detail_cache', map);
    }
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
    await _replaceInvitadoInCache(invitado);
    return invitado;
  }

  Future<InvitadoModel> updateInvitadoMenciones({
    required int invitadoId,
    required List<String> ayudas,
    required List<String> salud,
    required List<String> tramites,
    String? nota,
    SyncService? sync,
  }) async {
    final trimmedNota = nota?.trim();
    final payload = <String, dynamic>{
      'menciones_ayudas': ayudas,
      'menciones_salud': salud,
      'menciones_tramites': tramites,
      'menciones_nota': trimmedNota != null && trimmedNota.isNotEmpty ? trimmedNota : null,
    };

    if (!await _catalog.isOnline) {
      if (sync == null) {
        throw StateError('Se requiere conexión o servicio de sincronización para guardar menciones.');
      }

      await sync.enqueueInvitadoMencionesUpdate(
        invitadoId: invitadoId,
        ayudas: ayudas,
        salud: salud,
        tramites: tramites,
        nota: trimmedNota,
      );

      return _patchInvitadoMencionesInCache(invitadoId, payload);
    }

    final response = await _api.dio.put<Map<String, dynamic>>(
      '/invitados/$invitadoId/menciones',
      data: payload,
    );
    final data = response.data?['data'];
    if (data is! Map) {
      throw StateError('Respuesta inválida del servidor');
    }
    final invitado = InvitadoModel.fromJson(Map<String, dynamic>.from(data));
    await _replaceInvitadoInCache(invitado);
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

  List<HogarSolidarioInfo> _cachedHogares() {
    final raw = LocalDb.meta.get('hogares_cache');
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => HogarSolidarioInfo.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  int _cachedInvitadosCount() {
    final explicit = LocalDb.meta.get('hogares_invitados_count');
    if (explicit is int) return explicit;
    if (explicit is num) return explicit.toInt();
    return _cachedInvitados().length;
  }

  Map<String, dynamic> _hogarDetailToMap(HogarSolidarioDetail detail) => {
        'id': detail.id,
        'codigo': detail.codigo,
        'direccion_exacta': detail.direccionExacta,
        'tipo_vivienda_label': detail.tipoViviendaLabel,
        'tipo_anfitrion_label': detail.tipoAnfitrionLabel,
        'parentesco_anfitrion': detail.parentescoAnfitrion,
        'responsable_nombre': detail.responsableNombre,
        'responsable_cedula': detail.responsableCedula,
        'responsable_telefono': detail.responsableTelefono,
        'latitud': detail.latitud,
        'longitud': detail.longitud,
        'municipio': detail.municipio,
        'parroquia': detail.parroquia,
        'comuna': detail.comuna,
        'tiene_nucleo_familiar': detail.tieneNucleoFamiliar,
        'invitados_count': detail.invitadosCount,
        'registrado_el': detail.registradoEl,
        'jefe_familiar': detail.jefeFamiliar != null ? _invitadoToMap(detail.jefeFamiliar!) : null,
        'invitados': detail.invitados.map(_invitadoToMap).toList(),
        'partial': detail.partial,
      };

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
        'menciones_ayudas': i.mencionesAyudas,
        'menciones_salud': i.mencionesSalud,
        'menciones_tramites': i.mencionesTramites,
        'menciones_nota': i.mencionesNota,
        if (i.mencionesLabels != null)
          'menciones': {
            'ayudas': i.mencionesLabels!.ayudas.map((e) => {'value': e.value, 'label': e.label}).toList(),
            'salud': i.mencionesLabels!.salud.map((e) => {'value': e.value, 'label': e.label}).toList(),
            'tramites': i.mencionesLabels!.tramites.map((e) => {'value': e.value, 'label': e.label}).toList(),
            'nota': i.mencionesLabels!.nota,
          },
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
