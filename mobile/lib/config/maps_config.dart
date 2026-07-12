import 'package:visitantes_mobile/config/production_env.dart';

/// Google Maps en la app móvil (Android/iOS).
///
/// Dejar [kGoogleMapsAndroidEnabled] en `false` hasta registrar en Google Cloud:
/// Maps SDK for Android, package `com.visitantes.anzoategui.visitantes_mobile` y SHA-1 del keystore.
class MapsConfig {
  MapsConfig._();

  static const String _fromEnv = String.fromEnvironment('GOOGLE_MAPS_API_KEY');

  static String get apiKey =>
      _fromEnv.isNotEmpty ? _fromEnv : kGoogleMapsApiKey;

  /// `true` solo cuando la clave Android esté restringa y probada en dispositivo.
  static bool get useGoogleMapsOnMobile => kGoogleMapsAndroidEnabled && apiKey.isNotEmpty;
}
