import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' show LatLng;
import 'package:latlong2/latlong.dart' as ll;
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Centro aproximado de Anzoátegui.
const kAnzoateguiCenter = LatLng(9.8518, -64.3583);

ll.LatLng _toLatLong2(LatLng point) => ll.LatLng(point.latitude, point.longitude);

LatLng _toGoogleLatLng(ll.LatLng point) => LatLng(point.latitude, point.longitude);

/// Mapa OpenStreetMap — no depende de Google Maps API key.
class OsmMapView extends StatefulWidget {
  const OsmMapView({
    super.key,
    this.latitud,
    this.longitud,
    this.editable = true,
    this.onLocationChanged,
    this.initialZoom = 8,
    this.selectedZoom = 16,
    this.showAttribution = true,
  });

  final double? latitud;
  final double? longitud;
  final bool editable;
  final ValueChanged<LatLng>? onLocationChanged;
  final double initialZoom;
  final double selectedZoom;
  final bool showAttribution;

  @override
  State<OsmMapView> createState() => _OsmMapViewState();
}

class _OsmMapViewState extends State<OsmMapView> {
  final MapController _controller = MapController();

  ll.LatLng get _center {
    if (widget.latitud != null && widget.longitud != null) {
      return ll.LatLng(widget.latitud!, widget.longitud!);
    }
    return _toLatLong2(kAnzoateguiCenter);
  }

  double get _zoom =>
      widget.latitud != null && widget.longitud != null ? widget.selectedZoom : widget.initialZoom;

  @override
  void didUpdateWidget(covariant OsmMapView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.latitud != widget.latitud || oldWidget.longitud != widget.longitud) {
      _moveToSelection();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _moveToSelection() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _controller.move(_center, _zoom);
    });
  }

  void _handleTap(ll.LatLng point) {
    if (!widget.editable) return;
    widget.onLocationChanged?.call(_toGoogleLatLng(point));
  }

  @override
  Widget build(BuildContext context) {
    final hasPoint = widget.latitud != null && widget.longitud != null;

    return FlutterMap(
      mapController: _controller,
      options: MapOptions(
        initialCenter: _center,
        initialZoom: _zoom,
        minZoom: 5,
        maxZoom: 18,
        onTap: (_, point) => _handleTap(point),
        interactionOptions: InteractionOptions(
          flags: widget.editable
              ? InteractiveFlag.all
              : InteractiveFlag.all & ~InteractiveFlag.rotate,
        ),
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.visitantes.anzoategui.visitantes_mobile',
        ),
        if (hasPoint)
          MarkerLayer(
            markers: [
              Marker(
                point: ll.LatLng(widget.latitud!, widget.longitud!),
                width: 44,
                height: 44,
                alignment: Alignment.topCenter,
                child: const Icon(
                  Icons.location_on,
                  size: 40,
                  color: VenezuelaColors.red,
                ),
              ),
            ],
          ),
        if (widget.showAttribution)
          RichAttributionWidget(
            attributions: [
              TextSourceAttribution(
                'OpenStreetMap',
                onTap: () {},
              ),
            ],
          ),
      ],
    );
  }
}
