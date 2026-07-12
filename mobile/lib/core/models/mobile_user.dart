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
    this.tieneNucleoFamiliar = false,
    this.hogarVinculadoEn,
    this.hogarVinculoConocido = false,
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
  final bool tieneNucleoFamiliar;
  final String? hogarVinculadoEn;
  final bool hogarVinculoConocido;

  /// Hogar pre-asignado sin wizard cuando la API expone `hogar_vinculado_en` nulo.
  bool get debeRegistrarHogar =>
      requiereRegistroHogar ||
      (isAnfitrion && refugioId != null && hogarVinculoConocido && hogarVinculadoEn == null);

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
    bool? tieneNucleoFamiliar,
    String? hogarVinculadoEn,
    bool? hogarVinculoConocido,
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
      tieneNucleoFamiliar: tieneNucleoFamiliar ?? this.tieneNucleoFamiliar,
      hogarVinculadoEn: hogarVinculadoEn ?? this.hogarVinculadoEn,
      hogarVinculoConocido: hogarVinculoConocido ?? this.hogarVinculoConocido,
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
    final hogarVinculoConocido = json.containsKey('hogar_vinculado_en');

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
      tieneNucleoFamiliar: json['tiene_nucleo_familiar'] == true
          || refugio?['tiene_nucleo_familiar'] == true,
      hogarVinculadoEn: json['hogar_vinculado_en'] as String?,
      hogarVinculoConocido: hogarVinculoConocido,
    );
  }
}
