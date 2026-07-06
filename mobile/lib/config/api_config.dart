import 'dart:io' show Platform;

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:visitantes_mobile/config/production_env.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';

/// Resuelve la URL base de la API móvil (sin UI de configuración).
class ApiConfig {
  ApiConfig._();

  static String? _runtimeUrl;

  static String get apiBaseUrl => _runtimeUrl ?? kProductionApiBaseUrl;

  static Future<void> init() async {
    const fromEnv = String.fromEnvironment('API_BASE_URL');
    if (fromEnv.isNotEmpty) {
      _runtimeUrl = _normalize(fromEnv);
      ApiClient.instance.updateBaseUrl(_runtimeUrl!);
      return;
    }

    if (kDebugMode) {
      _runtimeUrl = await _debugDefault();
      ApiClient.instance.updateBaseUrl(_runtimeUrl!);
      return;
    }

    _runtimeUrl = kProductionApiBaseUrl;
    ApiClient.instance.updateBaseUrl(_runtimeUrl!);
  }

  static Future<String> _debugDefault() async {
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api/mobile';
    }

    if (Platform.isAndroid) {
      final android = await DeviceInfoPlugin().androidInfo;
      if (!android.isPhysicalDevice) {
        return 'http://10.0.2.2:8000/api/mobile';
      }
    }

    if (Platform.isIOS) {
      final ios = await DeviceInfoPlugin().iosInfo;
      if (!ios.isPhysicalDevice) {
        return 'http://127.0.0.1:8000/api/mobile';
      }
    }

    // Dispositivo físico en debug: apunta al backend Railway (mismo que release).
    return kProductionApiBaseUrl;
  }

  static String _normalize(String raw) {
    var url = raw.trim().replaceAll(RegExp(r'/+$'), '');
    if (!url.endsWith('/api/mobile')) {
      url = '$url/api/mobile';
    }
    return url;
  }
}
