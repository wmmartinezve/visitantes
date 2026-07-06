class MobileUser {
  MobileUser({
    required this.id,
    required this.name,
    required this.email,
    required this.rol,
    this.refugioId,
    this.centroAcopioId,
    this.refugioNombre,
    this.centroNombre,
  });

  final int id;
  final String name;
  final String email;
  final String rol;
  final int? refugioId;
  final int? centroAcopioId;
  final String? refugioNombre;
  final String? centroNombre;

  bool get isAnfitrion => rol == 'anfitrion';
  bool get isCentroAcopio => rol == 'centro_acopio';

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
      centroNombre: centro?['nombre'] as String?,
    );
  }
}
