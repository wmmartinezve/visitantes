import 'package:flutter/foundation.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:visitantes_mobile/config/maps_config.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Centro aproximado de Anzoátegui (vista inicial del mapa).
const kAnzoateguiCenter = LatLng(9.8518, -64.3583);

class CentroGeolocalizacionMap extends StatefulWidget {
  const CentroGeolocalizacionMap({
    super.key,
    required this.latitud,
    required this.longitud,
    required this.editable,
    this.onLocationChanged,
    this.height = 220,
    this.markerId = 'ubicacion',
    this.emptyHint = 'Toque el mapa o use el botón GPS para marcar la ubicación.',
    this.dragHint = 'Arrastre el pin para ajustar',
  });

  final double? latitud;
  final double? longitud;
  final bool editable;
  final ValueChanged<LatLng>? onLocationChanged;
  final double height;
  final String markerId;
  final String emptyHint;
  final String dragHint;

  @override
  State<CentroGeolocalizacionMap> createState() => _CentroGeolocalizacionMapState();
}

class _CentroGeolocalizacionMapState extends State<CentroGeolocalizacionMap> {
  GoogleMapController? _controller;

  LatLng get _cameraTarget {
    if (widget.latitud != null && widget.longitud != null) {
      return LatLng(widget.latitud!, widget.longitud!);
    }
    return kAnzoateguiCenter;
  }

  Set<Marker> get _markers {
    if (widget.latitud == null || widget.longitud == null) {
      return const {};
    }

    return {
      Marker(
        markerId: MarkerId(widget.markerId),
        position: LatLng(widget.latitud!, widget.longitud!),
        draggable: widget.editable,
        onDragEnd: widget.onLocationChanged,
      ),
    };
  }

  @override
  void didUpdateWidget(covariant CentroGeolocalizacionMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.latitud != widget.latitud || oldWidget.longitud != widget.longitud) {
      _moveCameraToSelection();
    }
  }

  Future<void> _moveCameraToSelection() async {
    final controller = _controller;
    if (controller == null || widget.latitud == null || widget.longitud == null) {
      return;
    }

    await controller.animateCamera(
      CameraUpdate.newLatLngZoom(
        LatLng(widget.latitud!, widget.longitud!),
        16,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!MapsConfig.isConfigured) {
      return _MapPlaceholder(
        height: widget.height,
        message: 'Configure GOOGLE_MAPS_API_KEY para ver el mapa.',
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(
        height: widget.height,
        child: Stack(
          children: [
            GoogleMap(
              initialCameraPosition: CameraPosition(
                target: _cameraTarget,
                zoom: widget.latitud != null ? 16 : 8,
              ),
              gestureRecognizers: const <Factory<OneSequenceGestureRecognizer>>{
                Factory<EagerGestureRecognizer>(EagerGestureRecognizer.new),
              },
              onMapCreated: (controller) {
                _controller = controller;
                _moveCameraToSelection();
              },
              markers: _markers,
              onTap: widget.editable ? widget.onLocationChanged : null,
              myLocationEnabled: widget.editable,
              myLocationButtonEnabled: false,
              zoomControlsEnabled: false,
              mapToolbarEnabled: false,
              compassEnabled: false,
            ),
            if (widget.editable && widget.latitud == null)
              Positioned(
                left: 8,
                right: 8,
                bottom: 8,
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.65),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    child: Text(
                      widget.emptyHint,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.white),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
              ),
            if (widget.editable && widget.latitud != null)
              Positioned(
                right: 8,
                top: 8,
                child: Material(
                  color: Colors.white,
                  elevation: 2,
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.touch_app, size: 16, color: VenezuelaColors.blue),
                        const SizedBox(width: 4),
                        Text(
                          widget.dragHint,
                          style: Theme.of(context).textTheme.labelSmall,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _MapPlaceholder extends StatelessWidget {
  const _MapPlaceholder({required this.height, required this.message});

  final double height;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      padding: const EdgeInsets.all(16),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.bodySmall,
      ),
    );
  }
}
