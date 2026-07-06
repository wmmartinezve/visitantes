import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class AppTheme {
  static ThemeData light() {
    const scheme = ColorScheme(
      brightness: Brightness.light,
      primary: VenezuelaColors.blue,
      onPrimary: VenezuelaColors.onBlue,
      primaryContainer: VenezuelaColors.blueContainer,
      onPrimaryContainer: VenezuelaColors.onBlueContainer,
      secondary: VenezuelaColors.red,
      onSecondary: VenezuelaColors.onBlue,
      secondaryContainer: VenezuelaColors.redContainer,
      onSecondaryContainer: VenezuelaColors.onRedContainer,
      tertiary: Color(0xFF9A7200),
      onTertiary: VenezuelaColors.onBlue,
      tertiaryContainer: VenezuelaColors.yellowContainer,
      onTertiaryContainer: VenezuelaColors.onYellowContainer,
      error: Color(0xFFBA1A1A),
      onError: Colors.white,
      surface: VenezuelaColors.surface,
      onSurface: Color(0xFF1A1C1E),
      onSurfaceVariant: Color(0xFF45464F),
      outline: Color(0xFF767680),
      outlineVariant: Color(0xFFC6C5D0),
      shadow: Colors.black,
      scrim: Colors.black,
      inverseSurface: Color(0xFF2F3033),
      onInverseSurface: Color(0xFFF1F0F4),
      inversePrimary: Color(0xFFADC6FF),
      surfaceTint: VenezuelaColors.blue,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: VenezuelaColors.surface,
      textTheme: _textTheme,
      appBarTheme: const AppBarTheme(
        centerTitle: false,
        backgroundColor: VenezuelaColors.blue,
        foregroundColor: VenezuelaColors.onBlue,
        elevation: 0,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: VenezuelaColors.surfaceContainer,
        indicatorColor: VenezuelaColors.blueContainer,
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: VenezuelaColors.blue);
          }
          return const TextStyle(fontSize: 12, color: Color(0xFF45464F));
        }),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: VenezuelaColors.blue);
          }
          return const IconThemeData(color: Color(0xFF45464F));
        }),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: VenezuelaColors.blue,
          foregroundColor: VenezuelaColors.onBlue,
          minimumSize: const Size.fromHeight(52),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: VenezuelaColors.blue,
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          side: const BorderSide(color: VenezuelaColors.blue),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: VenezuelaColors.blue,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.6)),
        ),
        focusedBorder: const OutlineInputBorder(
          borderRadius: BorderRadius.all(Radius.circular(16)),
          borderSide: BorderSide(color: VenezuelaColors.blue, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: scheme.error, width: 2),
        ),
        floatingLabelBehavior: FloatingLabelBehavior.auto,
        labelStyle: TextStyle(color: scheme.onSurfaceVariant, fontSize: 14),
        floatingLabelStyle: const TextStyle(color: VenezuelaColors.blue, fontWeight: FontWeight.w500),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: Color(0x1A002776)),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: VenezuelaColors.yellowContainer,
        labelStyle: const TextStyle(color: VenezuelaColors.onYellowContainer, fontSize: 12),
        side: BorderSide.none,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
      ),
      datePickerTheme: DatePickerThemeData(
        headerBackgroundColor: VenezuelaColors.blue,
        headerForegroundColor: VenezuelaColors.onBlue,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
    );
  }

  static const _textTheme = TextTheme(
    titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w600, color: Color(0xFF1A1C1E)),
    titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Color(0xFF1A1C1E)),
    titleSmall: TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF1A1C1E)),
    bodyLarge: TextStyle(fontSize: 16, color: Color(0xFF1A1C1E)),
    bodyMedium: TextStyle(fontSize: 14, color: Color(0xFF45464F)),
    bodySmall: TextStyle(fontSize: 12, color: Color(0xFF45464F)),
    labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
  );
}
