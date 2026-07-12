import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/media/photo_compression.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';
import 'package:visitantes_mobile/shared/widgets/witness_photo_capture.dart';

class RegisterGuestScreen extends StatefulWidget {
  const RegisterGuestScreen({
    super.key,
    required this.catalog,
    required this.sync,
    required this.fieldApi,
    this.onRegistered,
  });

  final CatalogService catalog;
  final SyncService sync;
  final FieldApi fieldApi;
  final VoidCallback? onRegistered;

  @override
  State<RegisterGuestScreen> createState() => _RegisterGuestScreenState();
}

class _RegisterGuestScreenState extends State<RegisterGuestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombre = TextEditingController();
  final _apellido = TextEditingController();
  final _cedula = TextEditingController();
  final _telefono = TextEditingController();
  final _fechaNacimiento = TextEditingController();
  XFile? _foto;
  Uint8List? _fotoPreview;
  String? _fotoSizeLabel;
  bool _loadingPhoto = false;
  int? _procedenciaEstadoId;
  int? _procedenciaMunicipioId;
  int? _procedenciaParroquiaId;
  String? _situacionJefe;
  bool _saving = false;
  final List<_FamiliarForm> _familiares = [];

  void _agregarFamiliar() {
    setState(() => _familiares.add(_FamiliarForm()));
  }

  void _quitarFamiliar(int index) {
    setState(() {
      _familiares[index].dispose();
      _familiares.removeAt(index);
    });
  }

  @override
  void dispose() {
    for (final f in _familiares) {
      f.dispose();
    }
    _nombre.dispose();
    _apellido.dispose();
    _cedula.dispose();
    _telefono.dispose();
    _fechaNacimiento.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    setState(() => _loadingPhoto = true);
    try {
      final picker = ImagePicker();
      final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 100);
      if (file == null) return;

      final raw = await file.readAsBytes();
      final compressed = await PhotoCompression.compressWitnessPhoto(raw);
      if (!mounted) return;

      setState(() {
        _foto = file;
        _fotoPreview = compressed.bytes;
        _fotoSizeLabel = '${compressed.sizeLabel} (antes ${PhotoCompression.formatSize(compressed.originalBytes)})';
      });
    } finally {
      if (mounted) setState(() => _loadingPhoto = false);
    }
  }

  void _removePhoto() {
    setState(() {
      _foto = null;
      _fotoPreview = null;
      _fotoSizeLabel = null;
    });
  }

  Future<Map<String, dynamic>> _buildPayload() async {
    final payload = <String, dynamic>{
      'nombre': _nombre.text.trim(),
      'apellido': _apellido.text.trim(),
      'cedula': _cedula.text.trim().isEmpty ? null : _cedula.text.trim(),
      'telefono': _telefono.text.trim().isEmpty ? null : _telefono.text.trim(),
      'fecha_nacimiento': _fechaNacimiento.text.trim(),
      if (_procedenciaEstadoId != null) 'procedencia_estado_id': _procedenciaEstadoId,
      if (_procedenciaMunicipioId != null) 'procedencia_municipio_id': _procedenciaMunicipioId,
      if (_procedenciaParroquiaId != null) 'procedencia_parroquia_id': _procedenciaParroquiaId,
      if (_situacionJefe != null) 'situacion_jefe': _situacionJefe,
      'familiares': _familiares
          .where((f) =>
              f.nombre.text.trim().isNotEmpty &&
              f.apellido.text.trim().isNotEmpty &&
              (f.parentesco?.isNotEmpty ?? false))
          .map((f) => {
                'nombre': f.nombre.text.trim(),
                'apellido': f.apellido.text.trim(),
                'parentesco': f.parentesco,
                'cedula': f.cedula.text.trim().isEmpty ? null : f.cedula.text.trim(),
                'telefono': f.telefono.text.trim().isEmpty ? null : f.telefono.text.trim(),
                'fecha_nacimiento': f.fecha.text.trim(),
              })
          .toList(),
    };

    if (_fotoPreview != null && _fotoPreview!.isNotEmpty) {
      payload['foto_base64'] = 'data:image/jpeg;base64,${base64Encode(_fotoPreview!)}';
      payload['foto_mime'] = 'image/jpeg';
    }

    return payload;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);

    try {
      final payload = await _buildPayload();
      final online = await widget.catalog.isOnline;

      if (online) {
        await widget.fieldApi.registerInvitadoOnline(payload);
        _clearForm();
        widget.onRegistered?.call();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Invitado registrado en el servidor.')),
        );
        return;
      }

      await widget.sync.enqueue('invitado.registro', payload);

      _clearForm();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sin conexión — invitado guardado localmente. Se sincronizará al reconectar.')),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final data = e.response?.data;
      var message = 'No se pudo registrar en el servidor.';
      if (data is Map) {
        if (data['message'] is String) message = data['message'] as String;
        final errors = data['errors'];
        if (errors is Map) {
          for (final entry in errors.entries) {
            final value = entry.value;
            if (value is List && value.isNotEmpty) {
              message = value.first.toString();
              break;
            }
          }
        }
      }
      if (message == 'Server Error' || e.response?.statusCode == 500) {
        message = 'Error interno del servidor. Intente de nuevo o contacte al administrador.';
      }
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _clearForm() {
    _nombre.clear();
    _apellido.clear();
    _cedula.clear();
    _telefono.clear();
    _fechaNacimiento.clear();
    _procedenciaEstadoId = null;
    _procedenciaMunicipioId = null;
    _procedenciaParroquiaId = null;
    _situacionJefe = null;
    for (final f in _familiares) {
      f.dispose();
    }
    _familiares.clear();
    setState(() {
      _foto = null;
      _fotoPreview = null;
    });
  }

  Future<void> _pickDate(TextEditingController target) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year - 30),
      firstDate: DateTime(1920),
      lastDate: now,
    );
    if (picked != null) {
      target.text =
          '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    }
  }

  List<Map<String, dynamic>> get _estados {
    final raw = widget.catalog.cachedCatalog?['estados'] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  List<Map<String, dynamic>> get _municipiosProcedencia {
    if (_procedenciaEstadoId == null) return [];
    final raw = widget.catalog.cachedCatalog?['municipios'] as List<dynamic>? ?? [];
    return raw
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((m) => m['estado_id'] == _procedenciaEstadoId)
        .toList();
  }

  List<Map<String, dynamic>> get _parroquiasProcedencia {
    if (_procedenciaMunicipioId == null) return [];
    final raw = widget.catalog.cachedCatalog?['parroquias'] as List<dynamic>? ?? [];
    return raw
        .map((e) => Map<String, dynamic>.from(e as Map))
        .where((p) => p['municipio_id'] == _procedenciaMunicipioId)
        .toList();
  }

  List<Map<String, String>> get _situacionesJefe {
    final raw = widget.catalog.cachedCatalog?['situaciones_jefe'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }
    return const [
      {'value': 'trabajando', 'label': 'Trabajando'},
      {'value': 'desempleado', 'label': 'Desempleado'},
      {'value': 'pensionado', 'label': 'Pensionado'},
      {'value': 'estudiante', 'label': 'Estudiante'},
      {'value': 'otro', 'label': 'Otro'},
    ];
  }

  List<String> get _parentescos {
    final raw = widget.catalog.cachedCatalog?['parentescos'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw.map((e) => e.toString()).toList();
    }
    return AppConfig.parentescos;
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          FormSectionCard(
            title: 'Foto testigo de ingreso',
            icon: Icons.photo_camera_outlined,
            child: WitnessPhotoCapture(
              previewBytes: _fotoPreview,
              fileName: _foto?.name,
              sizeLabel: _fotoSizeLabel,
              loading: _loadingPhoto,
              onCapture: _pickPhoto,
              onRemove: _removePhoto,
            ),
          ),
          const SizedBox(height: 16),
          FormSectionCard(
            title: 'Datos del Invitado',
            icon: M3FieldIcons.person,
            child: Column(
              children: [
                M3TextField(
                  controller: _nombre,
                  label: 'Nombre',
                  icon: M3FieldIcons.person,
                  textInputAction: TextInputAction.next,
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                ),
                M3TextField(
                  controller: _apellido,
                  label: 'Apellido',
                  icon: M3FieldIcons.person,
                  textInputAction: TextInputAction.next,
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                ),
                M3TextField(
                  controller: _cedula,
                  label: 'Cédula',
                  icon: M3FieldIcons.badge,
                  textInputAction: TextInputAction.next,
                  keyboardType: TextInputType.text,
                ),
                M3TextField(
                  controller: _telefono,
                  label: 'Teléfono',
                  icon: M3FieldIcons.phone,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                ),
                M3TextField(
                  controller: _fechaNacimiento,
                  label: 'Fecha de nacimiento',
                  icon: M3FieldIcons.calendar,
                  readOnly: true,
                  onTap: () => _pickDate(_fechaNacimiento),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.event_outlined, color: VenezuelaColors.blue),
                    onPressed: () => _pickDate(_fechaNacimiento),
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          FormSectionCard(
            title: 'Procedencia y situación laboral',
            icon: Icons.map_outlined,
            child: Column(
              children: [
                M3SelectField(
                  label: 'Estado de procedencia',
                  icon: Icons.public_outlined,
                  value: _procedenciaEstadoId == null
                      ? null
                      : _estados.firstWhere((e) => e['id'] == _procedenciaEstadoId)['nombre'] as String?,
                  items: _estados.map((e) => e['nombre'] as String).toList(),
                  onChanged: (v) => setState(() {
                    _procedenciaEstadoId = v == null
                        ? null
                        : _estados.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                    _procedenciaMunicipioId = null;
                    _procedenciaParroquiaId = null;
                  }),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                ),
                M3SelectField(
                  label: 'Municipio de procedencia',
                  icon: Icons.location_city_outlined,
                  value: _procedenciaMunicipioId == null
                      ? null
                      : _municipiosProcedencia.firstWhere((e) => e['id'] == _procedenciaMunicipioId)['nombre'] as String?,
                  items: _municipiosProcedencia.map((e) => e['nombre'] as String).toList(),
                  onChanged: (v) => setState(() {
                    _procedenciaMunicipioId = v == null
                        ? null
                        : _municipiosProcedencia.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                    _procedenciaParroquiaId = null;
                  }),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                ),
                M3SelectField(
                  label: 'Parroquia de procedencia',
                  icon: Icons.place_outlined,
                  value: _procedenciaParroquiaId == null
                      ? null
                      : _parroquiasProcedencia.firstWhere((e) => e['id'] == _procedenciaParroquiaId)['nombre'] as String?,
                  items: _parroquiasProcedencia.map((e) => e['nombre'] as String).toList(),
                  onChanged: (v) => setState(() {
                    _procedenciaParroquiaId = v == null
                        ? null
                        : _parroquiasProcedencia.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                  }),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                ),
                M3SelectField(
                  label: 'Situación del jefe de familia',
                  icon: Icons.work_outline,
                  value: _situacionJefe == null
                      ? null
                      : _situacionesJefe.firstWhere((e) => e['value'] == _situacionJefe)['label'],
                  items: _situacionesJefe.map((e) => e['label']!).toList(),
                  onChanged: (v) => setState(() {
                    _situacionJefe = v == null
                        ? null
                        : _situacionesJefe.firstWhere((e) => e['label'] == v)['value'];
                  }),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          SectionHeader(
            title: 'Núcleo familiar',
            action: TextButton.icon(
              onPressed: _agregarFamiliar,
              icon: const Icon(Icons.person_add_outlined, size: 18),
              label: const Text('Agregar'),
            ),
          ),
          ...List.generate(_familiares.length, (index) {
            final f = _familiares[index];
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Icon(M3FieldIcons.family, size: 18, color: VenezuelaColors.blue),
                            const SizedBox(width: 6),
                            Text('Familiar ${index + 1}', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
                          ],
                        ),
                        IconButton(icon: const Icon(Icons.delete_outline, color: VenezuelaColors.red), onPressed: () => _quitarFamiliar(index)),
                      ],
                    ),
                    M3SelectField(
                      label: 'Parentesco',
                      icon: M3FieldIcons.family,
                      value: f.parentesco,
                      items: _parentescos,
                      onChanged: (v) => setState(() => f.parentesco = v),
                      validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                    ),
                    M3TextField(
                      controller: f.nombre,
                      label: 'Nombre',
                      icon: M3FieldIcons.person,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                    ),
                    M3TextField(
                      controller: f.apellido,
                      label: 'Apellido',
                      icon: M3FieldIcons.person,
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                    ),
                    M3TextFieldRaw(controller: f.cedula, label: 'Cédula', icon: M3FieldIcons.badge),
                    M3TextFieldRaw(controller: f.telefono, label: 'Teléfono', icon: M3FieldIcons.phone, keyboardType: TextInputType.phone),
                    M3TextField(
                      controller: f.fecha,
                      label: 'Fecha de nacimiento',
                      icon: M3FieldIcons.calendar,
                      readOnly: true,
                      onTap: () => _pickDate(f.fecha),
                      suffixIcon: IconButton(
                        icon: const Icon(Icons.event_outlined, color: VenezuelaColors.blue),
                        onPressed: () => _pickDate(f.fecha),
                      ),
                      validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                    ),
                  ],
                ),
              ),
            );
          }),
          const SizedBox(height: 8),
          FilledButton.icon(
            onPressed: _saving ? null : _submit,
            icon: _saving
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Icon(Icons.save_outlined),
            label: const Text('Guardar Invitado'),
            style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
          ),
        ],
      ),
    );
  }
}

class _FamiliarForm {
  _FamiliarForm()
      : nombre = TextEditingController(),
        apellido = TextEditingController(),
        cedula = TextEditingController(),
        telefono = TextEditingController(),
        fecha = TextEditingController();

  String? parentesco;
  final TextEditingController nombre;
  final TextEditingController apellido;
  final TextEditingController cedula;
  final TextEditingController telefono;
  final TextEditingController fecha;

  void dispose() {
    nombre.dispose();
    apellido.dispose();
    cedula.dispose();
    telefono.dispose();
    fecha.dispose();
  }
}
