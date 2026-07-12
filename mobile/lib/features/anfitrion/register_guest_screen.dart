import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/media/photo_compression.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/centro_geolocalizacion_map.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';
import 'package:visitantes_mobile/shared/widgets/witness_photo_capture.dart';

class RegisterGuestScreen extends StatefulWidget {
  const RegisterGuestScreen({
    super.key,
    required this.user,
    required this.catalog,
    required this.sync,
    required this.fieldApi,
    this.nucleoYaRegistrado = false,
    this.requiereRegistroHogar = false,
    this.registrarNuevoHogar = false,
    this.onRegistered,
    this.onUserUpdated,
    this.onRegistrarOtroHogar,
  });

  final MobileUser user;
  final CatalogService catalog;
  final SyncService sync;
  final FieldApi fieldApi;
  final bool nucleoYaRegistrado;
  final bool requiereRegistroHogar;
  final bool registrarNuevoHogar;
  final VoidCallback? onRegistered;
  final ValueChanged<MobileUser>? onUserUpdated;
  final VoidCallback? onRegistrarOtroHogar;

  @override
  State<RegisterGuestScreen> createState() => _RegisterGuestScreenState();
}

class _RegisterGuestScreenState extends State<RegisterGuestScreen> with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;
  final _formKey = GlobalKey<FormState>();
  int _step = 0;
  bool _saving = false;
  bool _loadingGps = false;
  bool _loadingCatalog = false;

  bool get _sinHogarAsignado =>
      widget.user.debeRegistrarHogar || widget.catalog.requiereRegistroHogar;

  bool get _incluyeHogar =>
      (_sinHogarAsignado || widget.registrarNuevoHogar) &&
      !(widget.nucleoYaRegistrado && !widget.registrarNuevoHogar);

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

  String? _labelForValue(List<Map<String, String>> options, String value) {
    for (final option in options) {
      if (option['value'] == value) {
        return option['label'];
      }
    }
    return null;
  }

  String? _valueForLabel(List<Map<String, String>> options, String? label) {
    if (label == null) return null;
    for (final option in options) {
      if (option['label'] == label) {
        return option['value'];
      }
    }
    return null;
  }

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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _applyDefaultHogarEstado();
      _ensureCatalogReady();
    });
  }

  Future<void> _ensureCatalogReady() async {
    if (widget.catalog.isReady) {
      _applyDefaultHogarEstado();
      return;
    }

    setState(() => _loadingCatalog = true);
    try {
      await widget.catalog.ensureCached();
      if (!widget.catalog.isReady) {
        await widget.catalog.ensureCached(force: true);
      }
    } finally {
      if (mounted) {
        setState(() => _loadingCatalog = false);
        _applyDefaultHogarEstado();
      }
    }
  }

  void _applyDefaultHogarEstado() {
    if (_hogarEstadoId != null) return;

    for (final estado in _estados) {
      if (estado['nombre'] == 'Anzoátegui') {
        setState(() => _hogarEstadoId = estado['id'] as int?);
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

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
      if (!mounted) return;
      _actualizarUbicacionHogar(LatLng(position.latitude, position.longitude));
    } finally {
      if (mounted) setState(() => _loadingGps = false);
    }
  }

  double? get _hogarLatitud => double.tryParse(_hogarLat.text.trim());

  double? get _hogarLongitud => double.tryParse(_hogarLng.text.trim());

  void _actualizarUbicacionHogar(LatLng posicion) {
    setState(() {
      _hogarLat.text = posicion.latitude.toStringAsFixed(8);
      _hogarLng.text = posicion.longitude.toStringAsFixed(8);
    });
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
      payload['registrar_nuevo_hogar'] = widget.registrarNuevoHogar || _sinHogarAsignado;
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
      if (_hogarLatitud == null || _hogarLongitud == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Marque la ubicación del hogar en el mapa o use GPS.')),
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisSize: MainAxisSize.min,
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
    );
  }

  Widget _buildHogarStep() {
    return FormSectionCard(
      title: 'Datos del hogar solidario',
      icon: Icons.home_work_outlined,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          M3SelectField(
            label: 'Tipo de vivienda',
            icon: Icons.apartment_outlined,
            value: _tipoVivienda == null ? null : _labelForValue(_tiposVivienda, _tipoVivienda!),
            items: _tiposVivienda.map((e) => e['label']!).toList(),
            onChanged: (v) => setState(() => _tipoVivienda = _valueForLabel(_tiposVivienda, v)),
          ),
          M3SelectField(
            label: '¿Quién recibe al Invitado?',
            icon: Icons.group_outlined,
            value: _labelForValue(_tiposAnfitrion, _tipoAnfitrion),
            items: _tiposAnfitrion.map((e) => e['label']!).toList(),
            onChanged: (v) => setState(() {
              _tipoAnfitrion = _valueForLabel(_tiposAnfitrion, v) ?? 'familiar';
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
            value: _nombreEstado(_hogarEstadoId),
            items: _estados.where((e) => e['nombre'] == 'Anzoátegui').map((e) => e['nombre'] as String).toList(),
            onChanged: (v) {
              if (v == null) return;
              for (final estado in _estados) {
                if (estado['nombre'] == v) {
                  setState(() => _hogarEstadoId = estado['id'] as int?);
                  return;
                }
              }
            },
          ),
          M3SelectField(
            key: ValueKey('hogar-municipio-$_hogarEstadoId'),
            label: 'Municipio',
            icon: Icons.location_city_outlined,
            value: _nombreMunicipio(_municipiosHogar, _hogarMunicipioId),
            items: _municipiosHogar.map((e) => e['nombre'] as String).toList(),
            onChanged: (v) => setState(() {
              if (v == null) {
                _hogarMunicipioId = null;
              } else {
                for (final municipio in _municipiosHogar) {
                  if (municipio['nombre'] == v) {
                    _hogarMunicipioId = municipio['id'] as int?;
                    break;
                  }
                }
              }
              _hogarParroquiaId = null;
              _hogarComunaId = null;
            }),
          ),
          M3SelectField(
            key: ValueKey('hogar-parroquia-$_hogarMunicipioId'),
            label: 'Parroquia',
            icon: Icons.place_outlined,
            value: _nombreParroquia(_parroquiasHogar, _hogarParroquiaId),
            items: _parroquiasHogar.map((e) => e['nombre'] as String).toList(),
            onChanged: (v) => setState(() {
              if (v == null) {
                _hogarParroquiaId = null;
              } else {
                for (final parroquia in _parroquiasHogar) {
                  if (parroquia['nombre'] == v) {
                    _hogarParroquiaId = parroquia['id'] as int?;
                    break;
                  }
                }
              }
              _hogarComunaId = null;
            }),
          ),
          M3SelectField(
            key: ValueKey('hogar-comuna-$_hogarParroquiaId'),
            label: 'Comuna (opcional)',
            icon: Icons.map_outlined,
            value: _hogarComunaId == null ? null : _nombreComuna(_hogarComunaId),
            items: ['Sin comuna', ..._comunasHogar.map((e) => e['nombre'] as String)],
            onChanged: (v) => setState(() {
              if (v == null || v == 'Sin comuna') {
                _hogarComunaId = null;
                return;
              }
              for (final comuna in _comunasHogar) {
                if (comuna['nombre'] == v) {
                  _hogarComunaId = comuna['id'] as int?;
                  return;
                }
              }
            }),
          ),
          M3TextField(
            controller: _hogarDireccion,
            label: 'Dirección exacta',
            icon: Icons.signpost_outlined,
            validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
          ),
          const SizedBox(height: 12),
          Text(
            'Ubicación del hogar',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          CentroGeolocalizacionMap(
            latitud: _hogarLatitud,
            longitud: _hogarLongitud,
            editable: true,
            height: 240,
            markerId: 'hogar_solidario',
            emptyHint: 'Toque el mapa o use «Usar ubicación GPS» para marcar el hogar.',
            onLocationChanged: _actualizarUbicacionHogar,
          ),
          if (_hogarLatitud != null && _hogarLongitud != null) ...[
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                'Lat: ${_hogarLatitud!.toStringAsFixed(6)} · Lng: ${_hogarLongitud!.toStringAsFixed(6)}',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ],
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: _loadingGps ? null : _capturarGps,
              icon: _loadingGps
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.gps_fixed),
              label: Text(_loadingGps ? 'Obteniendo GPS…' : 'Usar ubicación GPS'),
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
      mainAxisSize: MainAxisSize.min,
      children: [
        FormSectionCard(
          title: 'Datos del Invitado (jefe de familia)',
          icon: M3FieldIcons.person,
          child: Column(
            mainAxisSize: MainAxisSize.min,
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
            mainAxisSize: MainAxisSize.min,
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
      mainAxisSize: MainAxisSize.min,
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
      mainAxisSize: MainAxisSize.min,
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
              mainAxisSize: MainAxisSize.min,
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

  Widget _buildWizardFooter(bool isLastStep) {
    return Padding(
      padding: EdgeInsets.fromLTRB(0, 16, 0, 8 + MediaQuery.viewInsetsOf(context).bottom),
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
    );
  }

  String? _nombreEstado(int? id) {
    if (id == null) return null;
    for (final estado in _estados) {
      if (estado['id'] == id) return estado['nombre'] as String?;
    }
    return null;
  }

  String? _nombreMunicipio(List<Map<String, dynamic>> source, int? id) {
    if (id == null) return null;
    for (final item in source) {
      if (item['id'] == id) return item['nombre'] as String?;
    }
    return null;
  }

  String? _nombreParroquia(List<Map<String, dynamic>> source, int? id) {
    if (id == null) return null;
    for (final item in source) {
      if (item['id'] == id) return item['nombre'] as String?;
    }
    return null;
  }

  String? _nombreComuna(int? id) {
    if (id == null) return null;
    for (final item in _comunasHogar) {
      if (item['id'] == id) return item['nombre'] as String?;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (_loadingCatalog) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CircularProgressIndicator(color: VenezuelaColors.blue),
            const SizedBox(height: 16),
            Text(
              'Cargando catálogo geográfico…',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _ensureCatalogReady,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      );
    }

    if (!widget.catalog.isReady) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          const Icon(Icons.cloud_off, size: 48, color: VenezuelaColors.blue),
          const SizedBox(height: 16),
          Text(
            'Catálogo offline no disponible',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          const Text(
            'Conéctese a internet al menos una vez para descargar estados, municipios, parroquias y listas del formulario. '
            'La caché se guarda en el teléfono por 24 horas.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _ensureCatalogReady,
            icon: const Icon(Icons.download),
            label: const Text('Descargar catálogo'),
          ),
        ],
      );
    }

    if (widget.nucleoYaRegistrado && !widget.registrarNuevoHogar) {
      final hogarActivo = widget.user.refugioNombre ?? 'su hogar activo';
      return ListView(
        padding: const EdgeInsets.all(16),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          Card(
            color: VenezuelaColors.blue.withValues(alpha: 0.08),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  const Icon(Icons.home_work, color: VenezuelaColors.blue),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Hogar activo: $hogarActivo',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            color: VenezuelaColors.yellow.withValues(alpha: 0.15),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Row(
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
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: widget.onRegistrarOtroHogar ??
                        () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Use Inicio → Registrar otro hogar y núcleo para un nuevo hogar.'),
                            ),
                          );
                        },
                    icon: const Icon(Icons.add_home_work),
                    label: const Text('Registrar otro hogar y núcleo'),
                    style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
                  ),
                ],
              ),
            ),
          ),
        ],
      );
    }

    final isLastStep = _step >= _totalSteps - 1;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        _buildStepIndicator(),
        const SizedBox(height: 12),
        Form(
          key: _formKey,
          child: _buildStepContent(),
        ),
        _buildWizardFooter(isLastStep),
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
