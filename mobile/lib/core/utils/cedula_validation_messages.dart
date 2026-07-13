/// Mensajes de cédula para registro online/offline.
class CedulaValidationMessages {
  static const alreadyRegistered = 'Esta cédula ya está registrada.';
  static const repeatedInForm =
      'Hay una cédula repetida en este registro (jefe u otro familiar).';

  /// Normaliza y detecta cédulas repetidas dentro del formulario actual.
  ///
  /// [familiares] son cédulas de familiares (pueden ser vacías).
  static String? conflictInForm(String? jefeCedula, List<String?> familiares) {
    final seen = <String>{};
    final jefe = _normalize(jefeCedula);
    if (jefe != null) {
      seen.add(jefe);
    }

    for (final raw in familiares) {
      final cedula = _normalize(raw);
      if (cedula == null) continue;
      if (seen.contains(cedula)) {
        return repeatedInForm;
      }
      seen.add(cedula);
    }

    return null;
  }

  /// Extrae un mensaje claro desde el mapa `errors` de una respuesta 422.
  static String? fromValidationErrors(Map? errors) {
    if (errors == null || errors.isEmpty) return null;

    for (final entry in errors.entries) {
      final key = entry.key.toString();
      if (!_isCedulaField(key)) continue;

      final value = entry.value;
      final raw = value is List && value.isNotEmpty ? value.first.toString() : value?.toString() ?? '';
      if (raw.contains('repetida') || raw.contains('mismo registro')) {
        return repeatedInForm;
      }
      if (raw.contains('ya está registrada') ||
          raw.contains('ya ha sido registrado') ||
          raw.toLowerCase().contains('unique')) {
        return alreadyRegistered;
      }
      if (raw.isNotEmpty) return raw;
      return alreadyRegistered;
    }

    return null;
  }

  /// Primera línea útil de un payload de error Dio (message + errors).
  static String fromDioPayload(Object? data, {String fallback = 'No se pudo completar la operación.'}) {
    if (data is! Map) return fallback;

    final fromErrors = fromValidationErrors(data['errors'] is Map ? data['errors'] as Map : null);
    if (fromErrors != null) return fromErrors;

    final message = data['message'];
    if (message is String && message.trim().isNotEmpty && message != 'The given data was invalid.') {
      return message;
    }

    final errors = data['errors'];
    if (errors is Map) {
      for (final entry in errors.entries) {
        final value = entry.value;
        if (value is List && value.isNotEmpty) {
          return value.first.toString();
        }
      }
    }

    return fallback;
  }

  static bool _isCedulaField(String key) {
    return key == 'cedula' || key == 'jefe_cedula' || key.endsWith('.cedula');
  }

  static String? _normalize(String? value) {
    if (value == null) return null;
    final trimmed = value.trim();
    return trimmed.isEmpty ? null : trimmed.toLowerCase();
  }
}
