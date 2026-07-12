import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/media/photo_compression.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
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
    this.nucleoYaRegistrado = false,
    this.requiereRegistroHogar = false,
    this.onRegistered,
    this.onUserUpdated,
  });

  final CatalogService catalog;
  final SyncService sync;
  final FieldApi fieldApi;
  final bool nucleoYaRegistrado;
  final bool requiereRegistroHogar;
  final VoidCallback? onRegistered;
  final ValueChanged<MobileUser>? onUserUpdated;

  @override
  State<RegisterGuestScreen> createState() => _RegisterGuestScreenState();
}

class _RegisterGuestScreenState extends State<RegisterGuestScreen> {
  final _formKey = GlobalKey<FormState>();
  int _step = 0;
  bool _saving = false;
  bool _loadingGps = false;

  // Hogar solidario
  final _hogarDireccion = TextEditingController();
  final _hogarLat = TextEditingController();
  final _hogarLng = TextEditingController();
  final _responsableNombre = TextEditingController();
  final _responsableCedula = TextEditingController();
  final _responsableTelefono = TextEditingController();
  String? _tipoVivienda;
  String _tipoAnfitrion = 'familiar';
  String? _parentescoAnfitrion;
  int? _hogarEstadoId;
  int? _hogarMunicipioId;
  int? _hogarParroquiaId;
  int? _hogarComunaId;

  // Jefe de familia
  final _nombre = TextEditingController();
  final _apellido = TextEditingController();
  final _cedula = TextEditingController();
  final _telefono = TextEditingController();
  final _fechaNacimiento = TextEditingController();
  int? _procedenciaEstadoId;
  int? _procedenciaMunicipioId;
  int? _procedenciaParroquiaId;
  String? _situacionJefe;
  String _condicion = 'ninguna';

  XFile? _foto;
  Uint8List? _fotoPreview;
  String? _fotoSizeLabel;
  bool _loadingPhoto = false;
  final List<_FamiliarForm> _familiares = [];

  bool get _incluyeHogar => widget.requiereRegistroHogar && !widget.nucleoYaRegistrado;

  int get _totalSteps => _incluyeHogar ? 4 : 3;

  List<String> get _stepTitles {
    if (_incluyeHogar) {
      return const ['Hogar solidario', 'Jefe de familia', 'Familiares', 'Foto y confirmar'];
    }
    return const ['Jefe de familia', 'Familiares', 'Foto y confirmar'];
  }

  int get _logicalStep => _step;

  void _agregarFamiliar() => setState(() => _familiares.add(_FamiliarForm()));

  void _quitarFamiliar(int index) {
    setState(() {
      _familiares[index].dispose();
      _familiares.removeAt(index);
    });
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _initHogarEstado());
  }

  void _initHogarEstado() {
    if (_hogarEstadoId != null) {
      return;
    }

    for (final estado in _estados) {
      if (estado['nombre'] == 'Anzoátegui') {
        if (mounted) {
          setState(() => _hogarEstadoId = estado['id'] as int?);
        }
        break;
      }
    }
  }

  @override
  void dispose() {
    for (final f in _familiares) {
      f.dispose();
    }
    _hogarDireccion.dispose();
    _hogarLat.dispose();
    _hogarLng.dispose();
    _responsableNombre.dispose();
    _responsableCedula.dispose();
    _responsableTelefono.dispose();
    _nombre.dispose();
    _apellido.dispose();
    _cedula.dispose();
    _telefono.dispose();
    _fechaNacimiento.dispose();
    super.dispose();
  }

  Future<void> _capturarGps() async {
    setState(() => _loadingGps = true);
    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Active el GPS del dispositivo.')),
        );
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Se requiere permiso de ubicación.')),
        );
        return;
      }

      final position = await Geolocator.getCurrentPosition();
      if (!mounted) return;
      setState(() {
        _hogarLat.text = position.latitude.toStringAsFixed(8);
        _hogarLng.text = position.longitude.toStringAsFixed(8);
      });
    } finally {
      if (mounted) setState(() => _loadingGps = false);
    }
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
      'procedencia_estado_id': _procedenciaEstadoId,
      'procedencia_municipio_id': _procedenciaMunicipioId,
      'procedencia_parroquia_id': _procedenciaParroquiaId,
      'situacion_jefe': _situacionJefe,
      'condicion': _condicion,
      'familiares': _familiares
          .where((f) =>
              f.nombre.text.trim().isNotEmpty &&
              f.apellido.text.trim().isNotEmpty &&
              (f.parentesco?.isNotEmpty ?? false))
          .map((f) => {
                'nombre': f.nombre.text.trim(),
                'apellido': f.apellido.text.trim(),
                'parentesco': f.parentesco,
                'condicion': f.condicion,
                'cedula': f.cedula.text.trim().isEmpty ? null : f.cedula.text.trim(),
                'telefono': f.telefono.text.trim().isEmpty ? null : f.telefono.text.trim(),
                'fecha_nacimiento': f.fecha.text.trim(),
              })
          .toList(),
    };

    if (_incluyeHogar) {
      payload['hogar'] = {
        'tipo_vivienda': _tipoVivienda,
        'tipo_anfitrion': _tipoAnfitrion,
        'parentesco_anfitrion': _tipoAnfitrion == 'familiar' ? _parentescoAnfitrion : null,
        'parroquia_id': _hogarParroquiaId,
        'comuna_id': _hogarComunaId,
        'responsable_nombre': _responsableNombre.text.trim(),
        'responsable_cedula': _responsableCedula.text.trim().isEmpty ? null : _responsableCedula.text.trim(),
        'responsable_telefono': _responsableTelefono.text.trim().isEmpty ? null : _responsableTelefono.text.trim(),
        'direccion_exacta': _hogarDireccion.text.trim(),
        'latitud': double.parse(_hogarLat.text.trim()),
        'longitud': double.parse(_hogarLng.text.trim()),
        'habitantes': <Map<String, String>>[],
      };
    }

    if (_fotoPreview != null && _fotoPreview!.isNotEmpty) {
      payload['foto_base64'] = 'data:image/jpeg;base64,${base64Encode(_fotoPreview!)}';
      payload['foto_mime'] = 'image/jpeg';
    }

    return payload;
  }

  bool _validateCurrentStep() {
    final form = _formKey.currentState;
    if (form == null || !form.validate()) return false;

    if (_incluyeHogar && _logicalStep == 0) {
      if (_tipoVivienda == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Seleccione el tipo de vivienda.')),
        );
        return false;
      }
      if (_hogarMunicipioId == null || _hogarParroquiaId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Complete municipio y parroquia del hogar.')),
        );
        return false;
      }
      if (_tipoAnfitrion == 'familiar' && (_parentescoAnfitrion == null || _parentescoAnfitrion!.isEmpty)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Indique el parentesco cuando el hogar es de un familiar.')),
        );
        return false;
      }
    }

    final jefeStep = _incluyeHogar ? 1 : 0;
    if (_logicalStep == jefeStep) {
      if (_procedenciaEstadoId == null ||
          _procedenciaMunicipioId == null ||
          _procedenciaParroquiaId == null ||
          _situacionJefe == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Complete procedencia y situación laboral.')),
        );
        return false;
      }
    }

    return true;
  }

  void _nextStep() {
    if (!_validateCurrentStep()) return;
    if (_step < _totalSteps - 1) {
      setState(() => _step++);
    }
  }

  void _prevStep() {
    if (_step > 0) setState(() => _step--);
  }

  Future<void> _submit() async {
    if (widget.nucleoYaRegistrado) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Este hogar solidario ya tiene un núcleo familiar. '
            'Agregue familiares desde el detalle del Invitado en la lista.',
          ),
        ),
      );
      return;
    }

    if (!_validateCurrentStep()) return;

    setState(() => _saving = true);

    try {
      final payload = await _buildPayload();
      final online = await widget.catalog.isOnline;

      if (online) {
        final result = await widget.fieldApi.registerInvitadoOnline(payload);
        if (result.updatedUser != null) {
          widget.onUserUpdated?.call(result.updatedUser!);
        }
        _clearForm();
        widget.onRegistered?.call();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result.updatedUser != null
                  ? 'Hogar solidario y núcleo familiar registrados.'
                  : 'Invitado registrado en el servidor.',
            ),
          ),
        );
        return;
      }

      await widget.sync.enqueue('invitado.registro', payload);
      _clearForm();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sin conexión — registro guardado localmente. Se sincronizará al reconectar.')),
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
    _hogarDireccion.clear();
    _hogarLat.clear();
    _hogarLng.clear();
    _responsableNombre.clear();
    _responsableCedula.clear();
    _responsableTelefono.clear();
    _nombre.clear();
    _apellido.clear();
    _cedula.clear();
    _telefono.clear();
    _fechaNacimiento.clear();
    _procedenciaEstadoId = null;
    _procedenciaMunicipioId = null;
    _procedenciaParroquiaId = null;
    _situacionJefe = null;
    _condicion = 'ninguna';
    _tipoVivienda = null;
    _tipoAnfitrion = 'familiar';
    _parentescoAnfitrion = null;
    _hogarMunicipioId = null;
    _hogarParroquiaId = null;
    _hogarComunaId = null;
    for (final f in _familiares) {
      f.dispose();
    }
    _familiares.clear();
    setState(() {
      _step = 0;
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

  List<Map<String, dynamic>> get _municipios {
    final raw = widget.catalog.cachedCatalog?['municipios'] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  List<Map<String, dynamic>> get _parroquias {
    final raw = widget.catalog.cachedCatalog?['parroquias'] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  List<Map<String, dynamic>> get _comunas {
    final raw = widget.catalog.cachedCatalog?['comunas'] as List<dynamic>? ?? [];
    return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  List<Map<String, dynamic>> get _municipiosHogar {
    if (_hogarEstadoId == null) return const [];
    return _municipios.where((m) => m['estado_id'] == _hogarEstadoId).toList();
  }

  List<Map<String, dynamic>> get _municipiosProcedencia {
    if (_procedenciaEstadoId == null) return [];
    return _municipios.where((m) => m['estado_id'] == _procedenciaEstadoId).toList();
  }

  List<Map<String, dynamic>> get _parroquiasProcedencia {
    if (_procedenciaMunicipioId == null) return [];
    return _parroquias.where((p) => p['municipio_id'] == _procedenciaMunicipioId).toList();
  }

  List<Map<String, dynamic>> get _parroquiasHogar {
    if (_hogarMunicipioId == null) return [];
    return _parroquias.where((p) => p['municipio_id'] == _hogarMunicipioId).toList();
  }

  List<Map<String, dynamic>> get _comunasHogar {
    if (_hogarParroquiaId == null) return [];
    return _comunas.where((c) => c['parroquia_id'] == _hogarParroquiaId).toList();
  }

  List<Map<String, String>> get _situacionesJefe {
    final raw = widget.catalog.cachedCatalog?['situaciones_jefe'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }
    return AppConfig.situacionesJefe;
  }

  List<Map<String, String>> get _condiciones {
    final raw = widget.catalog.cachedCatalog?['condiciones'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }
    return AppConfig.condiciones;
  }

  List<Map<String, String>> get _tiposVivienda {
    final raw = widget.catalog.cachedCatalog?['tipos_vivienda'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }
    return const [
      {'value': 'casa', 'label': 'Casa'},
      {'value': 'edificio', 'label': 'Edificio'},
    ];
  }

  List<Map<String, String>> get _tiposAnfitrion {
    final raw = widget.catalog.cachedCatalog?['tipos_anfitrion'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw
          .map((e) => Map<String, dynamic>.from(e as Map))
          .map((e) => {'value': e['value'].toString(), 'label': e['label'].toString()})
          .toList();
    }
    return const [
      {'value': 'familiar', 'label': 'Familiar'},
      {'value': 'amigo', 'label': 'Amigo'},
    ];
  }

  List<String> get _parentescos {
    final raw = widget.catalog.cachedCatalog?['parentescos'] as List<dynamic>?;
    if (raw != null && raw.isNotEmpty) {
      return raw.map((e) => e.toString()).toList();
    }
    return AppConfig.parentescos;
  }

  Widget _buildStepIndicator() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Paso ${_step + 1} de $_totalSteps · ${_stepTitles[_step]}',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          LinearProgressIndicator(
            value: (_step + 1) / _totalSteps,
            backgroundColor: VenezuelaColors.blue.withValues(alpha: 0.15),
            color: VenezuelaColors.red,
            minHeight: 6,
            borderRadius: BorderRadius.circular(3),
          ),
        ],
      ),
    );
  }

  Widget _buildHogarStep() {
    return FormSectionCard(
      title: 'Datos del hogar solidario',
      icon: Icons.home_work_outlined,
      child: Column(
        children: [
          Card(
            color: VenezuelaColors.yellow.withValues(alpha: 0.12),
            child: const Padding(
              padding: EdgeInsets.all(10),
              child: Text(
                'El código del hogar se asignará automáticamente (municipio · parroquia · correlativo).',
              ),
            ),
          ),
          M3SelectField(
            label: 'Tipo de vivienda',
            icon: Icons.apartment_outlined,
            value: _tipoVivienda == null
                ? null
                : _tiposVivienda.firstWhere((e) => e['value'] == _tipoVivienda)['label'],
            items: _tiposVivienda.map((e) => e['label']!).toList(),
            onChanged: (v) => setState(() {
              _tipoVivienda = v == null ? null : _tiposVivienda.firstWhere((e) => e['label'] == v)['value'];
            }),
          ),
          M3SelectField(
            label: '¿Quién recibe al Invitado?',
            icon: Icons.group_outlined,
            value: _tiposAnfitrion.firstWhere((e) => e['value'] == _tipoAnfitrion)['label'],
            items: _tiposAnfitrion.map((e) => e['label']!).toList(),
            onChanged: (v) => setState(() {
              _tipoAnfitrion = v == null ? 'familiar' : _tiposAnfitrion.firstWhere((e) => e['label'] == v)['value']!;
              if (_tipoAnfitrion == 'amigo') {
                _parentescoAnfitrion = null;
              }
            }),
          ),
          if (_tipoAnfitrion == 'familiar')
            M3SelectField(
              label: 'Parentesco con el jefe de familia',
              icon: Icons.family_restroom_outlined,
              value: _parentescoAnfitrion,
              items: _parentescos,
              onChanged: (v) => setState(() => _parentescoAnfitrion = v),
            ),
          M3SelectField(
            label: 'Estado',
            icon: Icons.public_outlined,
            value: _hogarEstadoId == null
                ? null
                : _estados.firstWhere((e) => e['id'] == _hogarEstadoId)['nombre'] as String?,
            items: _estados.where((e) => e['nombre'] == 'Anzoátegui').map((e) => e['nombre'] as String).toList(),
            onChanged: (v) {
              if (v == null) return;
              final match = _estados.firstWhere((e) => e['nombre'] == v);
              setState(() => _hogarEstadoId = match['id'] as int?);
            },
          ),
          M3SelectField(
            label: 'Municipio',
            icon: Icons.location_city_outlined,
            value: _hogarMunicipioId == null
                ? null
                : _municipiosHogar.firstWhere((e) => e['id'] == _hogarMunicipioId)['nombre'] as String?,
            items: _municipiosHogar.map((e) => e['nombre'] as String).toList(),
            onChanged: (v) => setState(() {
              _hogarMunicipioId =
                  v == null ? null : _municipiosHogar.firstWhere((e) => e['nombre'] == v)['id'] as int?;
              _hogarParroquiaId = null;
              _hogarComunaId = null;
            }),
          ),
          M3SelectField(
            label: 'Parroquia',
            icon: Icons.place_outlined,
            value: _hogarParroquiaId == null
                ? null
                : _parroquiasHogar.firstWhere((e) => e['id'] == _hogarParroquiaId)['nombre'] as String?,
            items: _parroquiasHogar.map((e) => e['nombre'] as String).toList(),
            onChanged: (v) => setState(() {
              _hogarParroquiaId =
                  v == null ? null : _parroquiasHogar.firstWhere((e) => e['nombre'] == v)['id'] as int?;
              _hogarComunaId = null;
            }),
          ),
          M3SelectField(
            label: 'Comuna (opcional)',
            icon: Icons.map_outlined,
            value: _hogarComunaId == null
                ? null
                : _comunasHogar.firstWhere((e) => e['id'] == _hogarComunaId)['nombre'] as String?,
            items: ['Sin comuna', ..._comunasHogar.map((e) => e['nombre'] as String)],
            onChanged: (v) => setState(() {
              if (v == null || v == 'Sin comuna') {
                _hogarComunaId = null;
                return;
              }
              _hogarComunaId = _comunasHogar.firstWhere((e) => e['nombre'] == v)['id'] as int?;
            }),
          ),
          M3TextField(
            controller: _hogarDireccion,
            label: 'Dirección exacta',
            icon: Icons.signpost_outlined,
            validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
          ),
          Row(
            children: [
              Expanded(
                child: M3TextField(
                  controller: _hogarLat,
                  label: 'Latitud',
                  icon: Icons.my_location_outlined,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: M3TextField(
                  controller: _hogarLng,
                  label: 'Longitud',
                  icon: Icons.my_location_outlined,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                ),
              ),
            ],
          ),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: _loadingGps ? null : _capturarGps,
              icon: _loadingGps
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.gps_fixed),
              label: const Text('Usar ubicación GPS'),
            ),
          ),
          const Divider(height: 24),
          M3TextField(
            controller: _responsableNombre,
            label: 'Responsable del hogar',
            icon: M3FieldIcons.person,
            validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
          ),
          M3TextField(controller: _responsableCedula, label: 'Cédula del responsable', icon: M3FieldIcons.badge),
          M3TextField(
            controller: _responsableTelefono,
            label: 'Teléfono del responsable',
            icon: M3FieldIcons.phone,
            keyboardType: TextInputType.phone,
          ),
        ],
      ),
    );
  }

  Widget _buildJefeStep() {
    return Column(
      children: [
        FormSectionCard(
          title: 'Datos del Invitado (jefe de familia)',
          icon: M3FieldIcons.person,
          child: Column(
            children: [
              M3TextField(
                controller: _nombre,
                label: 'Nombre',
                icon: M3FieldIcons.person,
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
              ),
              M3TextField(
                controller: _apellido,
                label: 'Apellido',
                icon: M3FieldIcons.person,
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
              ),
              M3TextField(controller: _cedula, label: 'Cédula', icon: M3FieldIcons.badge),
              M3TextField(
                controller: _telefono,
                label: 'Teléfono',
                icon: M3FieldIcons.phone,
                keyboardType: TextInputType.phone,
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
                  _procedenciaEstadoId =
                      v == null ? null : _estados.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                  _procedenciaMunicipioId = null;
                  _procedenciaParroquiaId = null;
                }),
              ),
              M3SelectField(
                label: 'Municipio de procedencia',
                icon: Icons.location_city_outlined,
                value: _procedenciaMunicipioId == null
                    ? null
                    : _municipiosProcedencia.firstWhere((e) => e['id'] == _procedenciaMunicipioId)['nombre']
                        as String?,
                items: _municipiosProcedencia.map((e) => e['nombre'] as String).toList(),
                onChanged: (v) => setState(() {
                  _procedenciaMunicipioId = v == null
                      ? null
                      : _municipiosProcedencia.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                  _procedenciaParroquiaId = null;
                }),
              ),
              M3SelectField(
                label: 'Parroquia de procedencia',
                icon: Icons.place_outlined,
                value: _procedenciaParroquiaId == null
                    ? null
                    : _parroquiasProcedencia.firstWhere((e) => e['id'] == _procedenciaParroquiaId)['nombre']
                        as String?,
                items: _parroquiasProcedencia.map((e) => e['nombre'] as String).toList(),
                onChanged: (v) => setState(() {
                  _procedenciaParroquiaId = v == null
                      ? null
                      : _parroquiasProcedencia.firstWhere((e) => e['nombre'] == v)['id'] as int?;
                }),
              ),
              M3SelectField(
                label: 'Situación del jefe de familia',
                icon: Icons.work_outline,
                value: _situacionJefe == null
                    ? null
                    : _situacionesJefe.firstWhere((e) => e['value'] == _situacionJefe)['label'],
                items: _situacionesJefe.map((e) => e['label']!).toList(),
                onChanged: (v) => setState(() {
                  _situacionJefe =
                      v == null ? null : _situacionesJefe.firstWhere((e) => e['label'] == v)['value'];
                }),
              ),
              M3SelectField(
                label: 'Condición',
                icon: Icons.accessibility_new_outlined,
                value: _condiciones.firstWhere((e) => e['value'] == _condicion)['label'],
                items: _condiciones.map((e) => e['label']!).toList(),
                onChanged: (v) => setState(() {
                  _condicion = v == null
                      ? 'ninguna'
                      : _condiciones.firstWhere((e) => e['label'] == v)['value']!;
                }),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildFamiliaresStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SectionHeader(
          title: 'Núcleo familiar',
          action: TextButton.icon(
            onPressed: _agregarFamiliar,
            icon: const Icon(Icons.person_add_outlined, size: 18),
            label: const Text('Agregar'),
          ),
        ),
        if (_familiares.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'Opcional: agregue familiares del núcleo hospedado. Puede continuar sin familiares.',
              style: TextStyle(color: Colors.black54),
            ),
          ),
        ...List.generate(_familiares.length, (index) {
          final f = _familiares[index];
          return Card(
            margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Familiar ${index + 1}', style: Theme.of(context).textTheme.titleSmall),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, color: VenezuelaColors.red),
                        onPressed: () => _quitarFamiliar(index),
                      ),
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
                  M3SelectField(
                    label: 'Condición',
                    icon: Icons.accessibility_new_outlined,
                    value: _condiciones.firstWhere((e) => e['value'] == f.condicion)['label'],
                    items: _condiciones.map((e) => e['label']!).toList(),
                    onChanged: (v) => setState(() {
                      f.condicion = v == null
                          ? 'ninguna'
                          : _condiciones.firstWhere((e) => e['label'] == v)['value']!;
                    }),
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
                  M3TextFieldRaw(controller: f.telefono, label: 'Teléfono', icon: M3FieldIcons.phone),
                  M3TextField(
                    controller: f.fecha,
                    label: 'Fecha de nacimiento',
                    icon: M3FieldIcons.calendar,
                    readOnly: true,
                    onTap: () => _pickDate(f.fecha),
                    validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }

  Widget _buildFotoStep() {
    return Column(
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
        const SizedBox(height: 12),
        Card(
          margin: const EdgeInsets.symmetric(horizontal: 16),
          color: VenezuelaColors.blue.withValues(alpha: 0.08),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Resumen', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                if (_incluyeHogar) ...[
                  const Text('Hogar: código automático al guardar'),
                  Text(
                    'Acogida: ${_tiposAnfitrion.firstWhere((e) => e['value'] == _tipoAnfitrion)['label']}',
                  ),
                ],
                Text('Jefe: ${_nombre.text.trim()} ${_apellido.text.trim()}'),
                Text('Familiares: ${_familiares.length}'),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStepContent() {
    if (_incluyeHogar) {
      return switch (_logicalStep) {
        0 => _buildHogarStep(),
        1 => _buildJefeStep(),
        2 => _buildFamiliaresStep(),
        _ => _buildFotoStep(),
      };
    }

    return switch (_logicalStep) {
      0 => _buildJefeStep(),
      1 => _buildFamiliaresStep(),
      _ => _buildFotoStep(),
    };
  }

  @override
  Widget build(BuildContext context) {
    if (widget.nucleoYaRegistrado) {
      return ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            color: VenezuelaColors.yellow.withValues(alpha: 0.15),
            child: const Padding(
              padding: EdgeInsets.all(12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.info_outline, color: VenezuelaColors.blue),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Este hogar solidario ya tiene un núcleo familiar registrado (1 hogar = 1 núcleo). '
                      'Use la pestaña Invitados para ver el jefe de familia y agregar familiares.',
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    }

    final isLastStep = _step >= _totalSteps - 1;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (_incluyeHogar)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Card(
              color: VenezuelaColors.yellow.withValues(alpha: 0.12),
              child: const Padding(
                padding: EdgeInsets.all(10),
                child: Text(
                  'Primero registre su hogar solidario y luego el núcleo familiar hospedado. '
                  'Un hogar = una familia.',
                ),
              ),
            ),
          ),
        _buildStepIndicator(),
        Expanded(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _buildStepContent(),
                const SizedBox(height: 80),
              ],
            ),
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Row(
              children: [
                if (_step > 0)
                  OutlinedButton(onPressed: _saving ? null : _prevStep, child: const Text('Anterior'))
                else
                  const SizedBox.shrink(),
                const Spacer(),
                if (!isLastStep)
                  FilledButton(
                    onPressed: _saving ? null : _nextStep,
                    style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.blue),
                    child: const Text('Siguiente'),
                  )
                else
                  FilledButton.icon(
                    onPressed: _saving ? null : _submit,
                    icon: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Icon(Icons.check),
                    label: const Text('Registrar'),
                    style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
                  ),
              ],
            ),
          ),
        ),
      ],
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
  String condicion = 'ninguna';
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
