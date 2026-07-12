import 'package:flutter/foundation.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:visitantes_mobile/config/maps_config.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/centro_geolocalizacion_map.dart';

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
  GoogleMapController? _controller;
  LatLng? _seleccion;

  @override
  void initState() {
    super.initState();
    if (widget.initialLat != null && widget.initialLng != null) {
      _seleccion = LatLng(widget.initialLat!, widget.initialLng!);
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  LatLng get _cameraTarget => _seleccion ?? kAnzoateguiCenter;

  Set<Marker> get _markers {
    final punto = _seleccion;
    if (punto == null) return const {};
    return {
      Marker(
        markerId: const MarkerId('picker'),
        position: punto,
        draggable: true,
        onDragEnd: (pos) => setState(() => _seleccion = pos),
      ),
    };
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
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
                  ? 'Toque el mapa para colocar el pin. Puede arrastrarlo para ajustar.'
                  : 'Lat: ${_seleccion!.latitude.toStringAsFixed(6)} · Lng: ${_seleccion!.longitude.toStringAsFixed(6)}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ),
          Expanded(
            child: MapsConfig.isConfigured
                ? GoogleMap(
                    initialCameraPosition: CameraPosition(
                      target: _cameraTarget,
                      zoom: _seleccion != null ? 16 : 8,
                    ),
                    gestureRecognizers: <Factory<OneSequenceGestureRecognizer>>{
                      Factory<EagerGestureRecognizer>(EagerGestureRecognizer.new),
                    },
                    onMapCreated: (controller) => _controller = controller,
                    markers: _markers,
                    onTap: (pos) => setState(() => _seleccion = pos),
                    myLocationEnabled: true,
                    myLocationButtonEnabled: true,
                    zoomControlsEnabled: true,
                    mapToolbarEnabled: false,
                  )
                : Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        'Configure GOOGLE_MAPS_API_KEY para usar el mapa.',
                        textAlign: TextAlign.center,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  ),
          ),
          SafeArea(
            minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: FilledButton.icon(
              onPressed: _confirmar,
              icon: const Icon(Icons.check),
              label: const Text('Confirmar ubicación'),
              style: FilledButton.styleFrom(
                backgroundColor: VenezuelaColors.red,
                minimumSize: const Size.fromHeight(48),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
