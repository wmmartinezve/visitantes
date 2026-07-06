import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Iconos estándar para campos de formulario (alineados con la web M3).
abstract final class M3FieldIcons {
  static const email = Icons.mail_outline_rounded;
  static const password = Icons.lock_outline_rounded;
  static const person = Icons.person_outline_rounded;
  static const badge = Icons.badge_outlined;
  static const phone = Icons.phone_outlined;
  static const calendar = Icons.calendar_today_outlined;
  static const search = Icons.search_rounded;
  static const item = Icons.shopping_bag_outlined;
  static const quantity = Icons.numbers_rounded;
  static const category = Icons.category_outlined;
  static const unit = Icons.straighten_rounded;
  static const inventory = Icons.inventory_2_outlined;
  static const family = Icons.family_restroom_outlined;
}
abstract final class M3InputStyles {
  static const fieldSpacing = 14.0;
  static const borderRadius = 16.0;

  static InputDecoration decoration({
    required BuildContext context,
    required String label,
    required IconData icon,
    String? hint,
    Widget? suffixIcon,
    String? errorText,
    bool enabled = true,
  }) {
    final scheme = Theme.of(context).colorScheme;

    return InputDecoration(
      labelText: label,
      hintText: hint,
      errorText: errorText,
      filled: true,
      fillColor: enabled ? Colors.white : VenezuelaColors.surfaceContainer,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
      prefixIcon: _IconBadge(icon: icon),
      prefixIconConstraints: const BoxConstraints(minWidth: 56, minHeight: 48),
      suffixIcon: suffixIcon,
      floatingLabelBehavior: FloatingLabelBehavior.auto,
      border: _border(scheme.outlineVariant),
      enabledBorder: _border(scheme.outlineVariant.withValues(alpha: 0.6)),
      focusedBorder: _border(VenezuelaColors.blue, width: 2),
      errorBorder: _border(scheme.error),
      focusedErrorBorder: _border(scheme.error, width: 2),
      disabledBorder: _border(scheme.outlineVariant.withValues(alpha: 0.35)),
      labelStyle: TextStyle(color: scheme.onSurfaceVariant, fontSize: 14),
      floatingLabelStyle: const TextStyle(color: VenezuelaColors.blue, fontWeight: FontWeight.w500),
      hintStyle: TextStyle(color: scheme.onSurfaceVariant.withValues(alpha: 0.7)),
      errorStyle: TextStyle(color: scheme.error, fontSize: 12),
    );
  }

  static OutlineInputBorder _border(Color color, {double width = 1}) {
    return OutlineInputBorder(
      borderRadius: BorderRadius.circular(borderRadius),
      borderSide: BorderSide(color: color, width: width),
    );
  }
}

class _IconBadge extends StatelessWidget {
  const _IconBadge({required this.icon});

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 12, right: 4),
      child: Container(
        width: 40,
        height: 40,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: VenezuelaColors.blueContainer,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Icon(icon, size: 20, color: VenezuelaColors.blue),
      ),
    );
  }
}

/// Campo de texto Material 3 con icono, para uso en formularios.
class M3TextField extends StatelessWidget {
  const M3TextField({
    super.key,
    required this.controller,
    required this.label,
    required this.icon,
    this.hint,
    this.validator,
    this.keyboardType,
    this.textInputAction,
    this.obscureText = false,
    this.readOnly = false,
    this.enabled = true,
    this.onTap,
    this.onChanged,
    this.onFieldSubmitted,
    this.suffixIcon,
    this.autofillHints,
    this.maxLines = 1,
    this.includeSpacing = true,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final String? hint;
  final String? Function(String?)? validator;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscureText;
  final bool readOnly;
  final bool enabled;
  final VoidCallback? onTap;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onFieldSubmitted;
  final Widget? suffixIcon;
  final Iterable<String>? autofillHints;
  final int maxLines;
  final bool includeSpacing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: includeSpacing ? M3InputStyles.fieldSpacing : 0),
      child: TextFormField(
        controller: controller,
        validator: validator,
        keyboardType: keyboardType,
        textInputAction: textInputAction,
        obscureText: obscureText,
        readOnly: readOnly,
        enabled: enabled,
        onTap: onTap,
        onChanged: onChanged,
        onFieldSubmitted: onFieldSubmitted,
        autofillHints: autofillHints,
        maxLines: maxLines,
        decoration: M3InputStyles.decoration(
          context: context,
          label: label,
          icon: icon,
          hint: hint,
          suffixIcon: suffixIcon,
          enabled: enabled,
        ),
      ),
    );
  }
}

/// Variante sin validación (Autocomplete, búsqueda interna).
class M3TextFieldRaw extends StatelessWidget {
  const M3TextFieldRaw({
    super.key,
    required this.controller,
    required this.label,
    required this.icon,
    this.focusNode,
    this.hint,
    this.keyboardType,
    this.onChanged,
    this.onSubmitted,
    this.suffixIcon,
    this.includeSpacing = true,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final FocusNode? focusNode;
  final String? hint;
  final TextInputType? keyboardType;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final Widget? suffixIcon;
  final bool includeSpacing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: includeSpacing ? M3InputStyles.fieldSpacing : 0),
      child: TextField(
        controller: controller,
        focusNode: focusNode,
        keyboardType: keyboardType,
        onChanged: onChanged,
        onSubmitted: onSubmitted,
        decoration: M3InputStyles.decoration(
          context: context,
          label: label,
          icon: icon,
          hint: hint,
          suffixIcon: suffixIcon,
        ),
      ),
    );
  }
}

/// Lista desplegable Material 3 con icono identificador.
class M3SelectField extends StatelessWidget {
  const M3SelectField({
    super.key,
    required this.label,
    required this.icon,
    required this.items,
    required this.onChanged,
    this.value,
    this.validator,
    this.includeSpacing = true,
  });

  final String label;
  final IconData icon;
  final List<String> items;
  final ValueChanged<String?> onChanged;
  final String? value;
  final String? Function(String?)? validator;
  final bool includeSpacing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      key: ValueKey('m3-select-$label-$value'),
      padding: EdgeInsets.only(bottom: includeSpacing ? M3InputStyles.fieldSpacing : 0),
      child: DropdownButtonFormField<String>(
        initialValue: value != null && value!.isNotEmpty && items.contains(value) ? value : null,
        isExpanded: true,
        decoration: M3InputStyles.decoration(context: context, label: label, icon: icon),
        hint: Text('Seleccione…', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
        items: items
            .map((item) => DropdownMenuItem<String>(value: item, child: Text(item)))
            .toList(),
        onChanged: onChanged,
        validator: validator,
      ),
    );
  }
}

/// Barra de búsqueda Material 3 (pill shape).
class M3SearchField extends StatelessWidget {
  const M3SearchField({
    super.key,
    required this.controller,
    required this.hint,
    required this.onSearch,
  });

  final TextEditingController controller;
  final String hint;
  final VoidCallback onSearch;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
      child: SearchBar(
        controller: controller,
        hintText: hint,
        leading: const Icon(Icons.search_rounded, color: VenezuelaColors.blue),
        trailing: [
          IconButton(
            icon: const Icon(Icons.arrow_forward_rounded, color: VenezuelaColors.red),
            onPressed: onSearch,
            tooltip: 'Buscar',
          ),
        ],
        onSubmitted: (_) => onSearch(),
        backgroundColor: WidgetStateProperty.all(Colors.white),
        elevation: WidgetStateProperty.all(1),
        shadowColor: WidgetStateProperty.all(VenezuelaColors.blue.withValues(alpha: 0.12)),
        shape: WidgetStateProperty.all(
          RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(28),
            side: BorderSide(color: Theme.of(context).colorScheme.outlineVariant.withValues(alpha: 0.5)),
          ),
        ),
        padding: WidgetStateProperty.all(const EdgeInsets.symmetric(horizontal: 4)),
      ),
    );
  }
}
