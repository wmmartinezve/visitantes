import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Franja tricolor amarillo · azul · rojo (bandera de Venezuela).
class VenezuelaTricolorBar extends StatelessWidget {
  const VenezuelaTricolorBar({super.key, this.height = 4});

  final double height;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: height,
      child: const Row(
        children: [
          Expanded(child: ColoredBox(color: VenezuelaColors.yellow)),
          Expanded(child: ColoredBox(color: VenezuelaColors.blue)),
          Expanded(child: ColoredBox(color: VenezuelaColors.red)),
        ],
      ),
    );
  }
}
