import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:visitantes_mobile/shared/widgets/osm_map_view.dart';

export 'package:visitantes_mobile/shared/widgets/osm_map_view.dart' show kAnzoateguiCenter;

/// Mapa de campo con OpenStreetMap (sin API key de Google en Android).
class FieldMapView extends StatelessWidget {
  const FieldMapView({
    super.key,
    this.latitud,
    this.longitud,
    this.editable = true,
    this.onLocationChanged,
    this.initialZoom = 8,
    this.selectedZoom = 16,
  });

  final double? latitud;
  final double? longitud;
  final bool editable;
  final ValueChanged<LatLng>? onLocationChanged;
  final double initialZoom;
  final double selectedZoom;

  @override
  Widget build(BuildContext context) {
    return OsmMapView(
      latitud: latitud,
      longitud: longitud,
      editable: editable,
      initialZoom: initialZoom,
      selectedZoom: selectedZoom,
      onLocationChanged: onLocationChanged,
    );
  }
}
