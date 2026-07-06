class InvitadoModel {
  InvitadoModel({
    required this.id,
    required this.nombreCompleto,
    this.cedula,
    this.telefono,
    this.estatusLabel,
    this.fechaNacimiento,
    this.edad,
    this.registradoEl,
    this.fotoUrl,
    this.esJefeFamilia = true,
    this.parentesco,
    this.detailInvitadoId,
    this.miembrosFamilia = const [],
    this.requerimientos = const [],
  });

  final int id;
  final String nombreCompleto;
  final String? cedula;
  final String? telefono;
  final String? estatusLabel;
  final String? fechaNacimiento;
  final int? edad;
  final String? registradoEl;
  final String? fotoUrl;
  final bool esJefeFamilia;
  final String? parentesco;
  final int? detailInvitadoId;
  final List<InvitadoMemberModel> miembrosFamilia;
  final List<RequerimientoModel> requerimientos;

  int get navigationId => detailInvitadoId ?? id;

  String get rolEnFamiliaLabel {
    if (esJefeFamilia) return 'Jefe de familia';
    if (parentesco != null && parentesco!.isNotEmpty) return parentesco!;

    return 'Familiar';
  }

  String? get edadLabel => edad != null ? '$edad años' : null;

  factory InvitadoModel.fromJson(Map<String, dynamic> json) {
    return InvitadoModel(
      id: json['id'] as int,
      nombreCompleto: json['nombre_completo'] as String? ?? '',
      cedula: json['cedula'] as String?,
      telefono: json['telefono'] as String?,
      estatusLabel: json['estatus_label'] as String?,
      fechaNacimiento: json['fecha_nacimiento'] as String?,
      edad: json['edad'] as int?,
      registradoEl: json['registrado_el'] as String?,
      fotoUrl: json['foto_url'] as String?,
      esJefeFamilia: json['es_jefe_familia'] as bool? ?? json['jefe_familia_id'] == null,
      parentesco: json['parentesco'] as String?,
      detailInvitadoId: json['detail_invitado_id'] as int?,
      miembrosFamilia: (json['miembros_familia'] as List<dynamic>? ?? [])
          .map((e) => InvitadoMemberModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      requerimientos: (json['requerimientos'] as List<dynamic>? ?? [])
          .map((e) => RequerimientoModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
    );
  }
}

class InvitadoMemberModel {
  InvitadoMemberModel({
    required this.id,
    required this.nombreCompleto,
    this.cedula,
    this.parentesco,
    this.edad,
  });

  final int id;
  final String nombreCompleto;
  final String? cedula;
  final String? parentesco;
  final int? edad;

  factory InvitadoMemberModel.fromJson(Map<String, dynamic> json) {
    return InvitadoMemberModel(
      id: json['id'] as int? ?? 0,
      nombreCompleto: json['nombre_completo'] as String? ?? '',
      cedula: json['cedula'] as String?,
      parentesco: json['parentesco'] as String?,
      edad: json['edad'] as int?,
    );
  }
}

class RequerimientoModel {
  RequerimientoModel({
    required this.id,
    required this.itemSolicitado,
    required this.cantidad,
    required this.estatus,
    required this.estatusLabel,
    this.invitadoId,
    this.invitadoNombre,
    this.centroAcopioNombre,
    this.refugioNombre,
    this.refugioDireccion,
    this.refugioLatitud,
    this.refugioLongitud,
    this.centroLatitud,
    this.centroLongitud,
    this.distanciaKm,
    this.rutaUrl,
    this.refugioUrl,
  });

  final int id;
  final String itemSolicitado;
  final int cantidad;
  final String estatus;
  final String estatusLabel;
  final int? invitadoId;
  final String? invitadoNombre;
  final String? centroAcopioNombre;
  final String? refugioNombre;
  final String? refugioDireccion;
  final double? refugioLatitud;
  final double? refugioLongitud;
  final double? centroLatitud;
  final double? centroLongitud;
  final double? distanciaKm;
  final String? rutaUrl;
  final String? refugioUrl;

  factory RequerimientoModel.fromJson(Map<String, dynamic> json) {
    return RequerimientoModel(
      id: json['id'] as int,
      itemSolicitado: json['item_solicitado'] as String? ?? '',
      cantidad: json['cantidad'] as int? ?? 0,
      estatus: json['estatus'] as String? ?? '',
      estatusLabel: json['estatus_label'] as String? ?? '',
      invitadoId: json['invitado_id'] as int?,
      invitadoNombre: json['invitado_nombre'] as String?,
      centroAcopioNombre: json['centro_acopio_nombre'] as String?,
      refugioNombre: json['refugio_nombre'] as String?,
      refugioDireccion: json['refugio_direccion'] as String?,
      refugioLatitud: (json['refugio_latitud'] as num?)?.toDouble(),
      refugioLongitud: (json['refugio_longitud'] as num?)?.toDouble(),
      centroLatitud: (json['centro_latitud'] as num?)?.toDouble(),
      centroLongitud: (json['centro_longitud'] as num?)?.toDouble(),
      distanciaKm: (json['distancia_km'] as num?)?.toDouble(),
      rutaUrl: json['ruta_url'] as String?,
      refugioUrl: json['refugio_url'] as String?,
    );
  }
}
