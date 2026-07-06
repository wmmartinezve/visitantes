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
  });

  final int id;
  final String name;
  final String email;
  final String rol;
  final int? refugioId;
  final int? centroAcopioId;
  final String? refugioNombre;
  final CentroAcopioInfo? centroAcopio;

  String? get centroNombre => centroAcopio?.nombre;

  bool get isAnfitrion => rol == 'anfitrion';
  bool get isCentroAcopio => rol == 'centro_acopio';

  MobileUser copyWith({CentroAcopioInfo? centroAcopio}) {
    return MobileUser(
      id: id,
      name: name,
      email: email,
      rol: rol,
      refugioId: refugioId,
      centroAcopioId: centroAcopioId,
      refugioNombre: refugioNombre,
      centroAcopio: centroAcopio ?? this.centroAcopio,
    );
  }

  factory MobileUser.fromJson(Map<String, dynamic> json) {
    final refugio = json['refugio'] as Map<String, dynamic>?;
    final centro = json['centro_acopio'] as Map<String, dynamic>?;

    return MobileUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      rol: json['rol'] as String,
      refugioId: json['refugio_id'] as int?,
      centroAcopioId: json['centro_acopio_id'] as int?,
      refugioNombre: refugio?['nombre'] as String?,
      centroAcopio: centro != null ? CentroAcopioInfo.fromJson(centro) : null,
    );
  }
}
