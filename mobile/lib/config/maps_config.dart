import 'package:visitantes_mobile/config/production_env.dart';

/// Clave de Google Maps para mapas embebidos en la app.
class MapsConfig {
  MapsConfig._();

  static const String _fromEnv = String.fromEnvironment('GOOGLE_MAPS_API_KEY');

  static String get apiKey =>
      _fromEnv.isNotEmpty ? _fromEnv : kGoogleMapsApiKey;

  static bool get isConfigured => apiKey.isNotEmpty;
}
