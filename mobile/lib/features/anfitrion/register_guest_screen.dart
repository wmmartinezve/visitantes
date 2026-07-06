import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/media/photo_compression.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';
import 'package:visitantes_mobile/shared/widgets/witness_photo_capture.dart';

class RegisterGuestScreen extends StatefulWidget {
  const RegisterGuestScreen({super.key, required this.catalog, required this.sync});

  final CatalogService catalog;
  final SyncService sync;

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

      await widget.sync.enqueue('invitado.registro', payload);

      if (online) {
        final result = await widget.sync.refreshAll();
        _clearForm();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result.sync.ok > 0
                  ? 'Invitado registrado en el servidor.'
                  : 'Invitado en cola — toque sync si no se envió.',
            ),
          ),
        );
        return;
      }

      _clearForm();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invitado guardado localmente. Sincronice cuando tenga red.')),
      );
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
