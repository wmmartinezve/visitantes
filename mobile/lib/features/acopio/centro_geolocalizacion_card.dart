import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class CentroGeolocalizacionCard extends StatefulWidget {
  const CentroGeolocalizacionCard({
    super.key,
    required this.user,
    required this.fieldApi,
    required this.catalog,
    required this.onUpdated,
  });

  final MobileUser user;
  final FieldApi fieldApi;
  final CatalogService catalog;
  final ValueChanged<MobileUser> onUpdated;

  @override
  State<CentroGeolocalizacionCard> createState() => _CentroGeolocalizacionCardState();
}

class _CentroGeolocalizacionCardState extends State<CentroGeolocalizacionCard> {
  final _direccionController = TextEditingController();
  bool _loadingGps = false;
  bool _saving = false;
  double? _latitud;
  double? _longitud;

  @override
  void initState() {
    super.initState();
    _syncFromUser(widget.user);
  }

  @override
  void didUpdateWidget(covariant CentroGeolocalizacionCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.user.centroAcopio?.id != widget.user.centroAcopio?.id) {
      _syncFromUser(widget.user);
    }
  }

  @override
  void dispose() {
    _direccionController.dispose();
    super.dispose();
  }

  void _syncFromUser(MobileUser user) {
    final centro = user.centroAcopio;
    _direccionController.text = centro?.direccionExacta ?? '';
    _latitud = centro?.latitud;
    _longitud = centro?.longitud;
  }

  bool get _editable => widget.user.centroAcopio?.geolocalizacionEditable ?? true;

  bool get _fijada => widget.user.centroAcopio?.tieneGeolocalizacion ?? false;

  Future<void> _fijarUbicacionGps() async {
    if (!_editable) return;

    setState(() => _loadingGps = true);

    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        throw Exception('Active el GPS del dispositivo e intente de nuevo.');
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied) {
        throw Exception('Permiso de ubicación denegado.');
      }

      if (permission == LocationPermission.deniedForever) {
        throw Exception('Permiso de ubicación bloqueado. Habilítelo en ajustes del teléfono.');
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );

      if (!mounted) return;
      setState(() {
        _latitud = position.latitude;
        _longitud = position.longitude;
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _loadingGps = false);
    }
  }

  Future<void> _guardar() async {
    if (!_editable) return;

    if (_latitud == null || _longitud == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Primero fije la ubicación con GPS.')),
      );
      return;
    }

    if (!await widget.catalog.isOnline) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Se requiere conexión a internet para guardar la ubicación.')),
      );
      return;
    }

    setState(() => _saving = true);

    try {
      final centro = await widget.fieldApi.updateCentroGeolocalizacion(
        latitud: _latitud!,
        longitud: _longitud!,
        direccionExacta: _direccionController.text,
      );

      widget.onUpdated(widget.user.copyWith(centroAcopio: centro));

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Ubicación registrada. No podrá modificarse nuevamente desde la app.'),
        ),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final data = e.response?.data;
      String message = 'No se pudo guardar la ubicación.';
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors['geolocalizacion'] is List && (errors['geolocalizacion'] as List).isNotEmpty) {
          message = (errors['geolocalizacion'] as List).first.toString();
        } else if (data['message'] is String) {
          message = data['message'] as String;
        }
      }
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo guardar la ubicación.')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final centro = widget.user.centroAcopio;
    final lat = _latitud ?? centro?.latitud;
    final lng = _longitud ?? centro?.longitud;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: VenezuelaColors.blue.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    _fijada ? Icons.lock : Icons.location_on,
                    color: VenezuelaColors.blue,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Georreferenciación del centro',
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                      ),
                      Text(
                        centro?.nombre ?? '—',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                Chip(
                  label: Text(
                    _fijada ? 'Fijada' : 'Pendiente',
                    style: TextStyle(
                      color: _fijada ? Colors.green.shade800 : Colors.orange.shade900,
                      fontSize: 12,
                    ),
                  ),
                  backgroundColor: _fijada ? Colors.green.shade50 : Colors.orange.shade50,
                  side: BorderSide.none,
                  visualDensity: VisualDensity.compact,
                ),
              ],
            ),
            if (_fijada) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.green.shade200),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.check_circle, color: Colors.green.shade700, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'La ubicación GPS ya fue registrada para este centro y no puede modificarse desde la app.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.green.shade900),
                      ),
                    ),
                  ],
                ),
              ),
            ] else ...[
              const SizedBox(height: 8),
              Text(
                'Solo podrá registrar la ubicación una vez. Verifique el punto exacto antes de guardar.',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
            const SizedBox(height: 12),
            if (_editable)
              TextField(
                controller: _direccionController,
                decoration: const InputDecoration(
                  labelText: 'Dirección exacta',
                  hintText: 'Calle, referencia, sector…',
                  border: OutlineInputBorder(),
                  isDense: true,
                ),
                maxLines: 2,
                textInputAction: TextInputAction.done,
              )
            else
              _ReadOnlyField(label: 'Dirección exacta', value: centro?.direccionExacta ?? '—'),
            const SizedBox(height: 12),
            if (lat != null && lng != null)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  'Lat: ${lat.toStringAsFixed(6)} · Lng: ${lng.toStringAsFixed(6)}',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(fontFeatures: const [FontFeature.tabularFigures()]),
                ),
              ),
            if (_editable) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _loadingGps || _saving ? null : _fijarUbicacionGps,
                      icon: _loadingGps
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.my_location),
                      label: Text(_loadingGps ? 'Obteniendo GPS…' : 'Fijar ubicación GPS'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _saving || _loadingGps ? null : _guardar,
                      style: FilledButton.styleFrom(
                        backgroundColor: VenezuelaColors.red,
                        foregroundColor: Colors.white,
                      ),
                      icon: _saving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.save),
                      label: const Text('Guardar'),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ReadOnlyField extends StatelessWidget {
  const _ReadOnlyField({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        isDense: true,
        filled: true,
      ),
      child: Text(value, style: Theme.of(context).textTheme.bodyMedium),
    );
  }
}
