/// Índices geográficos precalculados del catálogo offline (evita reparsear en cada build).
class GeoCatalogIndex {
  GeoCatalogIndex._({
    required this.estados,
    required this.municipiosByEstado,
    required this.parroquiasByMunicipio,
    required this.comunasByParroquia,
    required this.parentescos,
    required this.situacionesJefe,
    required this.condiciones,
    required this.tiposVivienda,
    required this.tiposAnfitrion,
  });

  final List<Map<String, dynamic>> estados;
  final Map<int, List<Map<String, dynamic>>> municipiosByEstado;
  final Map<int, List<Map<String, dynamic>>> parroquiasByMunicipio;
  final Map<int, List<Map<String, dynamic>>> comunasByParroquia;
  final List<String> parentescos;
  final List<Map<String, String>> situacionesJefe;
  final List<Map<String, String>> condiciones;
  final List<Map<String, String>> tiposVivienda;
  final List<Map<String, String>> tiposAnfitrion;

  static int? id(dynamic raw) {
    if (raw == null) return null;
    if (raw is int) return raw;
    if (raw is num) return raw.toInt();
    if (raw is String) return int.tryParse(raw.trim());
    return null;
  }

  static bool idEquals(dynamic raw, int? other) => id(raw) == other;

  static GeoCatalogIndex? from(Map<String, dynamic>? catalog) {
    if (catalog == null) return null;

    List<Map<String, dynamic>> parseList(String key) {
      final raw = catalog[key];
      if (raw is! List) return const [];
      return raw
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
    }

    List<Map<String, String>> parseEnumList(String key) {
      final raw = catalog[key];
      if (raw is! List || raw.isEmpty) return const [];
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }

    final estados = parseList('estados');
    final municipios = parseList('municipios');
    final parroquias = parseList('parroquias');
    final comunas = parseList('comunas');

    final municipiosByEstado = <int, List<Map<String, dynamic>>>{};
    for (final municipio in municipios) {
      final estadoId = id(municipio['estado_id']);
      if (estadoId == null) continue;
      municipiosByEstado.putIfAbsent(estadoId, () => []).add(municipio);
    }

    final parroquiasByMunicipio = <int, List<Map<String, dynamic>>>{};
    for (final parroquia in parroquias) {
      final municipioId = id(parroquia['municipio_id']);
      if (municipioId == null) continue;
      parroquiasByMunicipio.putIfAbsent(municipioId, () => []).add(parroquia);
    }

    final comunasByParroquia = <int, List<Map<String, dynamic>>>{};
    for (final comuna in comunas) {
      final parroquiaId = id(comuna['parroquia_id']);
      if (parroquiaId == null) continue;
      comunasByParroquia.putIfAbsent(parroquiaId, () => []).add(comuna);
    }

    final parentescosRaw = catalog['parentescos'];
    final parentescos = parentescosRaw is List
        ? parentescosRaw.map((e) => e.toString()).toList()
        : const <String>[];

    return GeoCatalogIndex._(
      estados: estados,
      municipiosByEstado: municipiosByEstado,
      parroquiasByMunicipio: parroquiasByMunicipio,
      comunasByParroquia: comunasByParroquia,
      parentescos: parentescos,
      situacionesJefe: parseEnumList('situaciones_jefe'),
      condiciones: parseEnumList('condiciones'),
      tiposVivienda: parseEnumList('tipos_vivienda'),
      tiposAnfitrion: parseEnumList('tipos_anfitrion'),
    );
  }

  List<Map<String, dynamic>> municipiosDeEstado(int? estadoId) {
    if (estadoId == null) return const [];
    return municipiosByEstado[estadoId] ?? const [];
  }

  List<Map<String, dynamic>> parroquiasDeMunicipio(int? municipioId) {
    if (municipioId == null) return const [];
    return parroquiasByMunicipio[municipioId] ?? const [];
  }

  List<Map<String, dynamic>> comunasDeParroquia(int? parroquiaId) {
    if (parroquiaId == null) return const [];
    return comunasByParroquia[parroquiaId] ?? const [];
  }

  String? nombreEnLista(List<Map<String, dynamic>> source, int? id) {
    if (id == null) return null;
    for (final item in source) {
      if (idEquals(item['id'], id)) return item['nombre'] as String?;
    }
    return null;
  }

  String? nombreEstado(int? id) => nombreEnLista(estados, id);
}
