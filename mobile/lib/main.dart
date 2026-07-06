import 'package:flutter/material.dart';
import 'package:visitantes_mobile/config/api_config.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';
import 'package:visitantes_mobile/core/theme/app_theme.dart';
import 'package:visitantes_mobile/features/auth/auth_gate.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalDb.init();
  await ApiConfig.init();
  runApp(const VisitantesApp());
}

class VisitantesApp extends StatelessWidget {
  const VisitantesApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Visitantes · Anzoátegui',
      theme: AppTheme.light(),
      home: const AuthGate(),
      debugShowCheckedModeBanner: false,
    );
  }
}
