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

  static const situacionesJefe = [
    {'value': 'trabajando', 'label': 'Trabajando'},
    {'value': 'desempleado', 'label': 'Desempleado'},
    {'value': 'pensionado', 'label': 'Pensionado'},
    {'value': 'estudiante', 'label': 'Estudiante'},
    {'value': 'otro', 'label': 'Otro'},
  ];

  static const condiciones = [
    {'value': 'ninguna', 'label': 'Ninguna'},
    {'value': 'discapacidad', 'label': 'Discapacidad'},
    {'value': 'embarazada', 'label': 'Embarazada'},
    {'value': 'adulto_mayor', 'label': 'Adulto mayor'},
  ];
}
