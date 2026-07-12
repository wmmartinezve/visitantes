import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/field_map_view.dart';

export 'package:visitantes_mobile/shared/widgets/field_map_view.dart' show kAnzoateguiCenter;

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
    this.scrollFriendly = true,
  });

  final double? latitud;
  final double? longitud;
  final bool editable;
  final ValueChanged<LatLng>? onLocationChanged;
  final double height;
  final String markerId;
  final String emptyHint;
  final String dragHint;
  final bool scrollFriendly;

  @override
  State<CentroGeolocalizacionMap> createState() => _CentroGeolocalizacionMapState();
}

class _CentroGeolocalizacionMapState extends State<CentroGeolocalizacionMap> {
  bool _interaccionActiva = false;

  bool get _montarMapa => !widget.scrollFriendly || _interaccionActiva;

  @override
  Widget build(BuildContext context) {
    if (!_montarMapa) {
      return _ScrollMapLauncher(
        height: widget.height,
        hint: widget.emptyHint,
        hasLocation: widget.latitud != null && widget.longitud != null,
        onActivate: () => setState(() => _interaccionActiva = true),
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(
        height: widget.height,
        child: Stack(
          children: [
            FieldMapView(
              latitud: widget.latitud,
              longitud: widget.longitud,
              editable: widget.editable,
              onLocationChanged: (posicion) {
                widget.onLocationChanged?.call(posicion);
                if (widget.scrollFriendly && mounted) {
                  setState(() => _interaccionActiva = false);
                }
              },
            ),
            if (widget.scrollFriendly)
              Positioned(
                left: 8,
                bottom: 8,
                child: Material(
                  color: Colors.white,
                  elevation: 2,
                  borderRadius: BorderRadius.circular(8),
                  child: InkWell(
                    onTap: () => setState(() => _interaccionActiva = false),
                    borderRadius: BorderRadius.circular(8),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.close, size: 16, color: VenezuelaColors.blue),
                          const SizedBox(width: 4),
                          Text('Cerrar mapa', style: Theme.of(context).textTheme.labelSmall),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            if (widget.editable && widget.latitud == null)
              Positioned(
                left: 8,
                right: 8,
                bottom: widget.scrollFriendly ? 44 : 8,
                child: IgnorePointer(
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
              ),
          ],
        ),
      ),
    );
  }
}

class _ScrollMapLauncher extends StatelessWidget {
  const _ScrollMapLauncher({
    required this.height,
    required this.hint,
    required this.hasLocation,
    required this.onActivate,
  });

  final double height;
  final String hint;
  final bool hasLocation;
  final VoidCallback onActivate;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surfaceContainerHighest.withValues(alpha: 0.45),
      borderRadius: BorderRadius.circular(12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onActivate,
        child: SizedBox(
          height: height,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  hasLocation ? Icons.location_on : Icons.map_outlined,
                  size: 40,
                  color: VenezuelaColors.blue,
                ),
                const SizedBox(height: 10),
                Text(
                  hasLocation ? 'Ubicación marcada. Toque para ver o ajustar en el mapa.' : hint,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                const SizedBox(height: 10),
                Text(
                  'Deslice el formulario con normalidad. El mapa se abre solo al tocar aquí.',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(color: Colors.black54),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
