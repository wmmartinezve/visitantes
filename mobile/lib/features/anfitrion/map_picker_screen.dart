import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/field_map_view.dart';

/// Pantalla completa para marcar ubicación sin conflictos con ListView.
class MapPickerScreen extends StatefulWidget {
  const MapPickerScreen({
    super.key,
    this.initialLat,
    this.initialLng,
    this.title = 'Marcar ubicación',
  });

  final double? initialLat;
  final double? initialLng;
  final String title;

  @override
  State<MapPickerScreen> createState() => _MapPickerScreenState();
}

class _MapPickerScreenState extends State<MapPickerScreen> {
  LatLng? _seleccion;

  @override
  void initState() {
    super.initState();
    if (widget.initialLat != null && widget.initialLng != null) {
      _seleccion = LatLng(widget.initialLat!, widget.initialLng!);
    }
  }

  void _confirmar() {
    final punto = _seleccion;
    if (punto == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Toque el mapa para marcar un punto.')),
      );
      return;
    }
    Navigator.of(context).pop(punto);
  }

  void _volver() => Navigator.of(context).pop();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          tooltip: 'Volver al paso anterior',
          onPressed: _volver,
        ),
        title: Text(widget.title),
        backgroundColor: VenezuelaColors.blue,
        foregroundColor: Colors.white,
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: Text(
              _seleccion == null
                  ? 'Toque el mapa para colocar el pin. Use los controles para acercar o alejar.'
                  : 'Lat: ${_seleccion!.latitude.toStringAsFixed(6)} · Lng: ${_seleccion!.longitude.toStringAsFixed(6)}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ),
          Expanded(
            child: FieldMapView(
              latitud: _seleccion?.latitude,
              longitud: _seleccion?.longitude,
              onLocationChanged: (point) => setState(() => _seleccion = point),
            ),
          ),
          SafeArea(
            minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                OutlinedButton.icon(
                  onPressed: _volver,
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Volver al wizard'),
                ),
                const SizedBox(height: 8),
                FilledButton.icon(
                  onPressed: _confirmar,
                  icon: const Icon(Icons.check),
                  label: const Text('Confirmar ubicación'),
                  style: FilledButton.styleFrom(
                    backgroundColor: VenezuelaColors.red,
                    minimumSize: const Size.fromHeight(48),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
