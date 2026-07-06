import 'package:visitantes_mobile/core/models/field_models.dart';

/// Enlaces a mapas (Google Maps) para navegación en entregas.
class GeoLinks {
  GeoLinks._();

  static String mapsQueryUrl(double lat, double lng) {
    return 'https://www.google.com/maps?q=$lat,$lng';
  }

  static String directionsUrl({
    required double fromLat,
    required double fromLng,
    required double toLat,
    required double toLng,
  }) {
    return 'https://www.google.com/maps/dir/?api=1'
        '&origin=$fromLat,$fromLng'
        '&destination=$toLat,$toLng';
  }

  static String? refugioUrlFor(RequerimientoModel entrega) {
    if (entrega.refugioUrl != null && entrega.refugioUrl!.isNotEmpty) {
      return entrega.refugioUrl;
    }
    final lat = entrega.refugioLatitud;
    final lng = entrega.refugioLongitud;
    if (lat != null && lng != null) {
      return mapsQueryUrl(lat, lng);
    }
    return null;
  }

  static String? rutaUrlFor(RequerimientoModel entrega) {
    if (entrega.rutaUrl != null && entrega.rutaUrl!.isNotEmpty) {
      return entrega.rutaUrl;
    }
    final fromLat = entrega.centroLatitud;
    final fromLng = entrega.centroLongitud;
    final toLat = entrega.refugioLatitud;
    final toLng = entrega.refugioLongitud;
    if (fromLat != null && fromLng != null && toLat != null && toLng != null) {
      return directionsUrl(fromLat: fromLat, fromLng: fromLng, toLat: toLat, toLng: toLng);
    }
    return null;
  }

  static bool canNavigate(RequerimientoModel entrega) => rutaUrlFor(entrega) != null;

  static bool canViewRefugio(RequerimientoModel entrega) => refugioUrlFor(entrega) != null;
}
