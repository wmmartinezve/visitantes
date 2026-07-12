import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/features/anfitrion/anfitrion_shell.dart';
import 'package:visitantes_mobile/features/auth/auth_repository.dart';
import 'package:visitantes_mobile/features/auth/forgot_password_screen.dart';
import 'package:visitantes_mobile/features/auth/login_screen.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  final _auth = AuthRepository();
  final _catalog = CatalogService();
  late final _fieldApi = FieldApi(catalogService: _catalog);
  late final _sync = SyncService(catalogService: _catalog, fieldApi: _fieldApi);

  bool _loading = true;
  bool _showForgotPassword = false;
  MobileUser? _user;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    var user = await _auth.restoreSession();
    if (user != null) {
      _sync.startAutoSync();
      await _catalog.ensureCached();
      try {
        user = await _auth.fetchCurrentUser();
        await _catalog.syncOperadorFromUser(user);
        await _fieldApi.refreshAnfitrionCaches();
      } catch (_) {}
      await _sync.syncPending();
    }
    if (!mounted) return;
    setState(() {
      _user = user;
      _loading = false;
    });
  }

  Future<void> _handleLogin(String email, String password) async {
    try {
      var user = await _auth.login(email, password);
      if (!user.isAnfitrion) {
        await _auth.logout();
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Esta app móvil está disponible solo para anfitriones de hogares solidarios.'),
          ),
        );
        return;
      }

      _sync.startAutoSync();
      await _catalog.ensureCached(force: true);
      user = await _auth.fetchCurrentUser();
      await _catalog.syncOperadorFromUser(user);
      await _fieldApi.refreshAnfitrionCaches();
      await _sync.syncPending();
      setState(() => _user = user);
    } on DioException catch (e) {
      final isNetwork = e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.sendTimeout ||
          e.type == DioExceptionType.receiveTimeout;

      final message = isNetwork
          ? 'No se pudo conectar con el servidor. Verifique su conexión a internet e intente de nuevo.'
          : e.response?.data is Map
              ? (e.response!.data['message'] as String? ?? 'Error de autenticación')
              : 'No se pudo conectar con el servidor';
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    }
  }

  Future<void> _logout() async {
    _sync.stopAutoSync();
    await _catalog.clear();
    await _auth.logout();
    setState(() => _user = null);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final user = _user;
    if (user == null) {
      if (_showForgotPassword) {
        return ForgotPasswordScreen(
          auth: _auth,
          onBack: () => setState(() => _showForgotPassword = false),
        );
      }

      return LoginScreen(
        onLogin: _handleLogin,
        onForgotPassword: () => setState(() => _showForgotPassword = true),
      );
    }

    if (!user.isAnfitrion) {
      return Scaffold(
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.info_outline, size: 48),
                const SizedBox(height: 16),
                Text(
                  'Acceso no disponible',
                  style: Theme.of(context).textTheme.titleLarge,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                const Text(
                  'La app móvil está pensada para anfitriones de hogares solidarios.',
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                FilledButton(onPressed: _logout, child: const Text('Cerrar sesión')),
              ],
            ),
          ),
        ),
      );
    }

    return AnfitrionShell(
      user: user,
      catalog: _catalog,
      sync: _sync,
      auth: _auth,
      onLogout: _logout,
      onUserUpdated: (updated) => setState(() => _user = updated),
    );
  }
}
