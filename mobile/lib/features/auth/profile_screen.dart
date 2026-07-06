import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/auth/auth_repository.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({
    super.key,
    required this.user,
    required this.auth,
    required this.onUserUpdated,
  });

  final MobileUser user;
  final AuthRepository auth;
  final ValueChanged<MobileUser> onUserUpdated;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _profileFormKey = GlobalKey<FormState>();
  final _passwordFormKey = GlobalKey<FormState>();

  late final TextEditingController _name;
  late final TextEditingController _email;
  final _currentPassword = TextEditingController();
  final _newPassword = TextEditingController();
  final _confirmPassword = TextEditingController();

  bool _savingProfile = false;
  bool _savingPassword = false;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.user.name);
    _email = TextEditingController(text: widget.user.email);
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _currentPassword.dispose();
    _newPassword.dispose();
    _confirmPassword.dispose();
    super.dispose();
  }

  Future<void> _saveProfile() async {
    if (!_profileFormKey.currentState!.validate()) return;

    setState(() => _savingProfile = true);
    try {
      final updated = await widget.auth.updateProfile(
        name: _name.text.trim(),
        email: _email.text.trim(),
      );
      widget.onUserUpdated(updated);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Perfil actualizado correctamente.')),
      );
    } on DioException catch (e) {
      _showError(e, 'No se pudo actualizar el perfil.');
    } finally {
      if (mounted) setState(() => _savingProfile = false);
    }
  }

  Future<void> _savePassword() async {
    if (!_passwordFormKey.currentState!.validate()) return;

    setState(() => _savingPassword = true);
    try {
      await widget.auth.updatePassword(
        currentPassword: _currentPassword.text,
        password: _newPassword.text,
        passwordConfirmation: _confirmPassword.text,
      );
      _currentPassword.clear();
      _newPassword.clear();
      _confirmPassword.clear();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Contraseña actualizada correctamente.')),
      );
    } on DioException catch (e) {
      _showError(e, 'No se pudo actualizar la contraseña.');
    } finally {
      if (mounted) setState(() => _savingPassword = false);
    }
  }

  void _showError(DioException e, String fallback) {
    if (!mounted) return;
    final data = e.response?.data;
    String message = fallback;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          message = first.first.toString();
        }
      } else if (data['message'] is String) {
        message = data['message'] as String;
      }
    }
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final assignment = widget.user.isAnfitrion
        ? 'Refugio: ${widget.user.refugioNombre ?? '—'}'
        : 'Centro: ${widget.user.centroNombre ?? '—'}';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Mi perfil', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 4),
                Text(assignment, style: Theme.of(context).textTheme.bodySmall),
                const SizedBox(height: 16),
                Form(
                  key: _profileFormKey,
                  child: Column(
                    children: [
                      M3TextField(
                        controller: _name,
                        label: 'Nombre',
                        icon: M3FieldIcons.person,
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                      ),
                      M3TextField(
                        controller: _email,
                        label: 'Correo electrónico',
                        icon: M3FieldIcons.email,
                        keyboardType: TextInputType.emailAddress,
                        validator: (v) => (v == null || !v.contains('@')) ? 'Correo inválido' : null,
                      ),
                      const SizedBox(height: 8),
                      FilledButton(
                        onPressed: _savingProfile ? null : _saveProfile,
                        style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.blue),
                        child: _savingProfile
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                            : const Text('Guardar perfil'),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _passwordFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Cambiar contraseña', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 16),
                  M3TextField(
                    controller: _currentPassword,
                    label: 'Contraseña actual',
                    icon: M3FieldIcons.password,
                    obscureText: true,
                    validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                  ),
                  M3TextField(
                    controller: _newPassword,
                    label: 'Nueva contraseña',
                    icon: M3FieldIcons.password,
                    obscureText: true,
                    validator: (v) => (v == null || v.length < 8) ? 'Mínimo 8 caracteres' : null,
                  ),
                  M3TextField(
                    controller: _confirmPassword,
                    label: 'Confirmar contraseña',
                    icon: M3FieldIcons.password,
                    obscureText: true,
                    validator: (v) => v != _newPassword.text ? 'No coincide' : null,
                  ),
                  const SizedBox(height: 8),
                  FilledButton(
                    onPressed: _savingPassword ? null : _savePassword,
                    style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
                    child: _savingPassword
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Actualizar contraseña'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
