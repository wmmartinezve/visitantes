class InvitadoMencionOption {
  const InvitadoMencionOption({required this.value, required this.label});

  final String value;
  final String label;

  factory InvitadoMencionOption.fromJson(Map<String, dynamic> json) {
    return InvitadoMencionOption(
      value: json['value'] as String? ?? '',
      label: json['label'] as String? ?? '',
    );
  }

  static List<InvitadoMencionOption> parseList(dynamic raw) {
    if (raw is! List) return [];
    return raw
        .whereType<Map>()
        .map((e) => InvitadoMencionOption.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }
}

class MencionesCatalogo {
  const MencionesCatalogo({
    this.ayudas = const [],
    this.salud = const [],
    this.tramites = const [],
  });

  final List<InvitadoMencionOption> ayudas;
  final List<InvitadoMencionOption> salud;
  final List<InvitadoMencionOption> tramites;

  factory MencionesCatalogo.fromJson(Map<String, dynamic> json) {
    return MencionesCatalogo(
      ayudas: InvitadoMencionOption.parseList(json['ayudas']),
      salud: InvitadoMencionOption.parseList(json['salud']),
      tramites: InvitadoMencionOption.parseList(json['tramites']),
    );
  }

  List<InvitadoMencionOption> forCategory(String category) {
    return switch (category) {
      'ayudas' => ayudas,
      'salud' => salud,
      'tramites' => tramites,
      _ => const [],
    };
  }

  List<InvitadoMencionOption> labelsFor(String category, List<String> keys) {
    final options = forCategory(category);
    return keys
        .map((key) => options.where((o) => o.value == key).firstOrNull)
        .whereType<InvitadoMencionOption>()
        .toList();
  }
}

class InvitadoMencionesLabels {
  const InvitadoMencionesLabels({
    this.ayudas = const [],
    this.salud = const [],
    this.tramites = const [],
    this.nota,
  });

  final List<InvitadoMencionOption> ayudas;
  final List<InvitadoMencionOption> salud;
  final List<InvitadoMencionOption> tramites;
  final String? nota;

  bool get isEmpty =>
      ayudas.isEmpty && salud.isEmpty && tramites.isEmpty && (nota == null || nota!.trim().isEmpty);

  factory InvitadoMencionesLabels.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const InvitadoMencionesLabels();
    return InvitadoMencionesLabels(
      ayudas: InvitadoMencionOption.parseList(json['ayudas']),
      salud: InvitadoMencionOption.parseList(json['salud']),
      tramites: InvitadoMencionOption.parseList(json['tramites']),
      nota: json['nota'] as String?,
    );
  }
}

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
    this.hogarSolidarioId,
    this.hogarCodigo,
    this.miembrosFamilia = const [],
    this.requerimientos = const [],
    this.mencionesAyudas = const [],
    this.mencionesSalud = const [],
    this.mencionesTramites = const [],
    this.mencionesNota,
    this.mencionesLabels,
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
  final int? hogarSolidarioId;
  final String? hogarCodigo;
  final List<InvitadoMemberModel> miembrosFamilia;
  final List<RequerimientoModel> requerimientos;
  final List<String> mencionesAyudas;
  final List<String> mencionesSalud;
  final List<String> mencionesTramites;
  final String? mencionesNota;
  final InvitadoMencionesLabels? mencionesLabels;

  int get navigationId => detailInvitadoId ?? id;

  String get rolEnFamiliaLabel {
    if (esJefeFamilia) return 'Jefe de familia';
    if (parentesco != null && parentesco!.isNotEmpty) return parentesco!;

    return 'Familiar';
  }

  String? get edadLabel => edad != null ? '$edad años' : null;

  bool get tieneMenciones =>
      mencionesAyudas.isNotEmpty ||
      mencionesSalud.isNotEmpty ||
      mencionesTramites.isNotEmpty ||
      (mencionesNota != null && mencionesNota!.trim().isNotEmpty);

  InvitadoMencionesLabels resolveMencionesLabels(MencionesCatalogo? catalog) {
    if (mencionesLabels != null && !mencionesLabels!.isEmpty) {
      return mencionesLabels!;
    }

    if (catalog != null) {
      return InvitadoMencionesLabels(
        ayudas: catalog.labelsFor('ayudas', mencionesAyudas),
        salud: catalog.labelsFor('salud', mencionesSalud),
        tramites: catalog.labelsFor('tramites', mencionesTramites),
        nota: mencionesNota,
      );
    }

    List<InvitadoMencionOption> keysAsOptions(List<String> keys) {
      return keys.map((k) => InvitadoMencionOption(value: k, label: k)).toList();
    }

    return InvitadoMencionesLabels(
      ayudas: keysAsOptions(mencionesAyudas),
      salud: keysAsOptions(mencionesSalud),
      tramites: keysAsOptions(mencionesTramites),
      nota: mencionesNota,
    );
  }

  String get mencionesResumen {
    final labels = mencionesLabels;
    if (labels != null && !labels.isEmpty) {
      final parts = [
        ...labels.ayudas.map((e) => e.label),
        ...labels.salud.map((e) => e.label),
        ...labels.tramites.map((e) => e.label),
      ];
      if (parts.isEmpty) return 'Sin menciones';
      return parts.join(', ');
    }

    final count = mencionesAyudas.length + mencionesSalud.length + mencionesTramites.length;
    if (count == 0) return 'Sin menciones';
    return '$count seleccionada(s)';
  }

  InvitadoModel copyWithMenciones({
    List<String>? ayudas,
    List<String>? salud,
    List<String>? tramites,
    String? nota,
    InvitadoMencionesLabels? labels,
  }) {
    return InvitadoModel(
      id: id,
      nombreCompleto: nombreCompleto,
      cedula: cedula,
      telefono: telefono,
      estatusLabel: estatusLabel,
      fechaNacimiento: fechaNacimiento,
      edad: edad,
      registradoEl: registradoEl,
      fotoUrl: fotoUrl,
      esJefeFamilia: esJefeFamilia,
      parentesco: parentesco,
      detailInvitadoId: detailInvitadoId,
      hogarSolidarioId: hogarSolidarioId,
      hogarCodigo: hogarCodigo,
      miembrosFamilia: miembrosFamilia,
      requerimientos: requerimientos,
      mencionesAyudas: ayudas ?? mencionesAyudas,
      mencionesSalud: salud ?? mencionesSalud,
      mencionesTramites: tramites ?? mencionesTramites,
      mencionesNota: nota ?? mencionesNota,
      mencionesLabels: labels ?? mencionesLabels,
    );
  }

  static List<String> _parseStringList(dynamic raw) {
    if (raw is! List) return [];
    return raw.whereType<String>().toList();
  }

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
      hogarSolidarioId: json['hogar_solidario_id'] as int?,
      hogarCodigo: json['hogar_codigo'] as String?,
      miembrosFamilia: (json['miembros_familia'] as List<dynamic>? ?? [])
          .map((e) => InvitadoMemberModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      requerimientos: (json['requerimientos'] as List<dynamic>? ?? [])
          .map((e) => RequerimientoModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
      mencionesAyudas: _parseStringList(json['menciones_ayudas']),
      mencionesSalud: _parseStringList(json['menciones_salud']),
      mencionesTramites: _parseStringList(json['menciones_tramites']),
      mencionesNota: json['menciones_nota'] as String?,
      mencionesLabels: json['menciones'] is Map
          ? InvitadoMencionesLabels.fromJson(Map<String, dynamic>.from(json['menciones'] as Map))
          : null,
    );
  }
}

extension _FirstOrNullInvitadoMencion on Iterable<InvitadoMencionOption> {
  InvitadoMencionOption? get firstOrNull {
    final iterator = this.iterator;
    if (iterator.moveNext()) return iterator.current;
    return null;
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
