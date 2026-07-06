import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/auth/auth_repository.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';
import 'package:visitantes_mobile/shared/widgets/venezuela_tricolor_bar.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({
    super.key,
    required this.auth,
    required this.onBack,
  });

  final AuthRepository auth;
  final VoidCallback onBack;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  bool _loading = false;
  bool _sent = false;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);
    try {
      await widget.auth.sendPasswordResetEmail(_email.text.trim());
      if (!mounted) return;
      setState(() => _sent = true);
    } on DioException catch (e) {
      if (!mounted) return;
      final message = e.response?.data is Map
          ? (e.response!.data['message'] as String? ?? 'No se pudo enviar el correo.')
          : 'No se pudo enviar el correo.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          const VenezuelaTricolorBar(height: 6),
          Expanded(
            child: SafeArea(
              top: false,
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Recuperar contraseña',
                            style: Theme.of(context).textTheme.headlineSmall,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _sent
                                ? 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.'
                                : 'Ingrese su correo y le enviaremos un enlace para restablecer su contraseña.',
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 24),
                          if (!_sent) ...[
                            M3TextField(
                              controller: _email,
                              label: 'Correo electrónico',
                              icon: M3FieldIcons.email,
                              keyboardType: TextInputType.emailAddress,
                              validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                            ),
                            const SizedBox(height: 8),
                            FilledButton(
                              onPressed: _loading ? null : _submit,
                              style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.blue),
                              child: _loading
                                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Text('Enviar enlace'),
                            ),
                          ],
                          TextButton(
                            onPressed: widget.onBack,
                            child: const Text('Volver al inicio de sesión'),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          const VenezuelaTricolorBar(height: 4),
        ],
      ),
    );
  }
}
