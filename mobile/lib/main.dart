import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';
import 'package:visitantes_mobile/config/api_config.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';
import 'package:visitantes_mobile/core/theme/app_theme.dart';
import 'package:visitantes_mobile/features/auth/auth_gate.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalDb.init();
  await ApiConfig.init();
  await initializeDateFormatting('es_VE');
  Intl.defaultLocale = 'es_VE';
  runApp(const VisitantesApp());
}

class VisitantesApp extends StatelessWidget {
  const VisitantesApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Visitantes · Anzoátegui',
      theme: AppTheme.light(),
      locale: const Locale('es', 'VE'),
      supportedLocales: const [
        Locale('es', 'VE'),
        Locale('es'),
      ],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: const AuthGate(),
      debugShowCheckedModeBanner: false,
    );
  }
}
