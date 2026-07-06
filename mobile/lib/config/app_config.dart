import 'package:visitantes_mobile/config/api_config.dart';

class AppConfig {
  static String get apiBaseUrl => ApiConfig.apiBaseUrl;

  static const estado = 'Anzoátegui';

  static const parentescos = [
    'Cónyuge',
    'Hijo(a)',
    'Padre',
    'Madre',
    'Hermano(a)',
    'Abuelo(a)',
    'Nieto(a)',
    'Tío(a)',
    'Sobrino(a)',
    'Cuñado(a)',
    'Yerno / Nuera',
    'Suegro(a)',
    'Otro',
  ];
}
