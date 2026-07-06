import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

/// Abre enlaces de mapas en la app externa (Google Maps, navegador, etc.).
class MapLauncher {
  MapLauncher._();

  static Future<void> open(BuildContext context, String? url, {required String errorMessage}) async {
    if (url == null || url.trim().isEmpty) {
      _showError(context, errorMessage);
      return;
    }

    final uri = Uri.tryParse(url.trim());
    if (uri == null) {
      _showError(context, errorMessage);
      return;
    }

    try {
      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched && context.mounted) {
        _showError(context, errorMessage);
      }
    } catch (_) {
      if (context.mounted) {
        _showError(context, errorMessage);
      }
    }
  }

  static void _showError(BuildContext context, String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
}
