import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';
import 'package:visitantes_mobile/core/theme/app_theme.dart';
import 'package:visitantes_mobile/features/anfitrion/register_guest_flow.dart';

Map<String, dynamic> _sampleCatalog() {
  return {
    'estados': [
      {'id': 1, 'nombre': 'Anzoátegui'},
    ],
    'municipios': [
      {'id': 10, 'nombre': 'Barcelona', 'estado_id': 1},
    ],
    'parroquias': [
      {'id': 100, 'nombre': 'San Cristóbal', 'municipio_id': 10},
    ],
    'comunas': [],
    'parentescos': ['Padre', 'Madre', 'Hijo'],
    'tipos_vivienda': [
      {'value': 'casa', 'label': 'Casa'},
    ],
    'tipos_anfitrion': [
      {'value': 'familiar', 'label': 'Familiar'},
    ],
    'situaciones_jefe': [
      {'value': 'empleado', 'label': 'Empleado'},
    ],
    'condiciones': [
      {'value': 'ninguna', 'label': 'Ninguna'},
    ],
  };
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() async {
    final tempDir = await Directory.systemTemp.createTemp('visitantes_hive_test');
    Hive.init(tempDir.path);
    if (!Hive.isBoxOpen(LocalDb.catalogBox)) {
      await Hive.openBox<dynamic>(LocalDb.catalogBox);
    }
    if (!Hive.isBoxOpen(LocalDb.metaBox)) {
      await Hive.openBox<dynamic>(LocalDb.metaBox);
    }
    await LocalDb.catalog.clear();
    await LocalDb.meta.clear();
    await LocalDb.catalog.put('current', _sampleCatalog());
    await LocalDb.meta.put('catalog_cached_at', DateTime.now().toUtc().toIso8601String());
  });

  testWidgets('RegisterGuestFlow muestra paso 1 del wizard', (tester) async {
    final catalog = CatalogService();
    final user = MobileUser(
      id: 1,
      name: 'Anfitrión prueba',
      email: 'test@example.com',
      rol: 'anfitrion',
      puedeRegistrarOtroHogar: true,
      hogaresCount: 1,
    );

    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: MediaQuery(
          data: const MediaQueryData(size: Size(412, 892)),
          child: Scaffold(
            body: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Container(height: 72, color: Colors.blue),
                Container(height: 36, color: Colors.amber),
                Expanded(
                  child: RegisterGuestFlow(
                    user: user,
                    catalog: catalog,
                    sync: SyncService(),
                    fieldApi: FieldApi(catalogService: catalog),
                    nucleoYaRegistrado: false,
                    requiereRegistroHogar: false,
                    registrarNuevoHogar: true,
                  ),
                ),
              ],
            ),
            bottomNavigationBar: const SizedBox(height: 72),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.textContaining('Paso 1 de 5'), findsOneWidget);
    expect(find.textContaining('Datos del hogar solidario'), findsOneWidget);
    expect(find.text('Siguiente'), findsOneWidget);
  });
}
