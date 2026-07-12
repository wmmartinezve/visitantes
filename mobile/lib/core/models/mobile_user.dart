class CentroAcopioInfo {
  CentroAcopioInfo({
    required this.id,
    required this.nombre,
    this.direccionExacta,
    this.latitud,
    this.longitud,
    this.tieneGeolocalizacion = false,
    this.geolocalizacionEditable = true,
    this.geolocalizacionFijadaEn,
  });

  final int id;
  final String nombre;
  final String? direccionExacta;
  final double? latitud;
  final double? longitud;
  final bool tieneGeolocalizacion;
  final bool geolocalizacionEditable;
  final String? geolocalizacionFijadaEn;

  factory CentroAcopioInfo.fromJson(Map<String, dynamic> json) {
    return CentroAcopioInfo(
      id: json['id'] as int,
      nombre: json['nombre'] as String,
      direccionExacta: json['direccion_exacta'] as String?,
      latitud: (json['latitud'] as num?)?.toDouble(),
      longitud: (json['longitud'] as num?)?.toDouble(),
      tieneGeolocalizacion: json['tiene_geolocalizacion'] as bool? ?? false,
      geolocalizacionEditable: json['geolocalizacion_editable'] as bool? ?? true,
      geolocalizacionFijadaEn: json['geolocalizacion_fijada_en'] as String?,
    );
  }

  CentroAcopioInfo copyWith({
    String? direccionExacta,
    double? latitud,
    double? longitud,
    bool? tieneGeolocalizacion,
    bool? geolocalizacionEditable,
    String? geolocalizacionFijadaEn,
  }) {
    return CentroAcopioInfo(
      id: id,
      nombre: nombre,
      direccionExacta: direccionExacta ?? this.direccionExacta,
      latitud: latitud ?? this.latitud,
      longitud: longitud ?? this.longitud,
      tieneGeolocalizacion: tieneGeolocalizacion ?? this.tieneGeolocalizacion,
      geolocalizacionEditable: geolocalizacionEditable ?? this.geolocalizacionEditable,
      geolocalizacionFijadaEn: geolocalizacionFijadaEn ?? this.geolocalizacionFijadaEn,
    );
  }
}

class HogarSolidarioInfo {
  HogarSolidarioInfo({
    required this.id,
    required this.codigo,
    this.direccionExacta,
    this.tieneNucleoFamiliar = false,
    this.invitadosCount = 0,
    this.activo = false,
  });

  final int id;
  final String codigo;
  final String? direccionExacta;
  final bool tieneNucleoFamiliar;
  final int invitadosCount;
  final bool activo;

  factory HogarSolidarioInfo.fromJson(Map<String, dynamic> json) {
    return HogarSolidarioInfo(
      id: json['id'] as int,
      codigo: (json['codigo'] ?? json['nombre'] ?? 'Hogar') as String,
      direccionExacta: json['direccion_exacta'] as String?,
      tieneNucleoFamiliar: json['tiene_nucleo_familiar'] == true,
      invitadosCount: _parseInt(json['invitados_count']) ?? 0,
      activo: json['activo'] == true,
    );
  }

  static int? _parseInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String && value.isNotEmpty) return int.tryParse(value);
    return null;
  }
}

class MobileUser {
  MobileUser({
    required this.id,
    required this.name,
    required this.email,
    required this.rol,
    this.refugioId,
    this.centroAcopioId,
    this.refugioNombre,
    this.centroAcopio,
    this.requiereRegistroHogar = false,
    this.puedeRegistrarOtroHogar = false,
    this.hogaresCount = 0,
    this.hogares = const [],
    this.tieneNucleoFamiliar = false,
  });

  final int id;
  final String name;
  final String email;
  final String rol;
  final int? refugioId;
  final int? centroAcopioId;
  final String? refugioNombre;
  final CentroAcopioInfo? centroAcopio;
  final bool requiereRegistroHogar;
  final bool puedeRegistrarOtroHogar;
  final int hogaresCount;
  final List<HogarSolidarioInfo> hogares;
  final bool tieneNucleoFamiliar;

  bool get debeRegistrarHogar => requiereRegistroHogar;

  String? get centroNombre => centroAcopio?.nombre;

  bool get isAnfitrion => rol == 'anfitrion';
  bool get isCentroAcopio => rol == 'centro_acopio';

  MobileUser copyWith({
    String? name,
    String? email,
    int? refugioId,
    String? refugioNombre,
    CentroAcopioInfo? centroAcopio,
    bool? requiereRegistroHogar,
    bool? puedeRegistrarOtroHogar,
    int? hogaresCount,
    List<HogarSolidarioInfo>? hogares,
    bool? tieneNucleoFamiliar,
  }) {
    return MobileUser(
      id: id,
      name: name ?? this.name,
      email: email ?? this.email,
      rol: rol,
      refugioId: refugioId ?? this.refugioId,
      centroAcopioId: centroAcopioId,
      refugioNombre: refugioNombre ?? this.refugioNombre,
      centroAcopio: centroAcopio ?? this.centroAcopio,
      requiereRegistroHogar: requiereRegistroHogar ?? this.requiereRegistroHogar,
      puedeRegistrarOtroHogar: puedeRegistrarOtroHogar ?? this.puedeRegistrarOtroHogar,
      hogaresCount: hogaresCount ?? this.hogaresCount,
      hogares: hogares ?? this.hogares,
      tieneNucleoFamiliar: tieneNucleoFamiliar ?? this.tieneNucleoFamiliar,
    );
  }

  static int? _parseIntId(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String && value.isNotEmpty) return int.tryParse(value);
    return null;
  }

  factory MobileUser.fromJson(Map<String, dynamic> json) {
    final refugio = (json['refugio'] ?? json['hogar_solidario']) as Map<String, dynamic>?;
    final centro = json['centro_acopio'] as Map<String, dynamic>?;
    final hogaresRaw = json['hogares'] as List<dynamic>? ?? [];

    return MobileUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      rol: json['rol'] as String,
      refugioId: _parseIntId(json['refugio_id'] ?? json['hogar_solidario_id']),
      centroAcopioId: _parseIntId(json['centro_acopio_id']),
      refugioNombre: (refugio?['nombre'] ?? refugio?['codigo']) as String?,
      centroAcopio: centro != null ? CentroAcopioInfo.fromJson(centro) : null,
      requiereRegistroHogar: json['requiere_registro_hogar'] == true,
      puedeRegistrarOtroHogar: json['puede_registrar_otro_hogar'] == true,
      hogaresCount: _parseIntId(json['hogares_count']) ?? hogaresRaw.length,
      hogares: hogaresRaw
          .map((e) => HogarSolidarioInfo.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      tieneNucleoFamiliar: json['tiene_nucleo_familiar'] == true
          || refugio?['tiene_nucleo_familiar'] == true,
    );
  }
}
