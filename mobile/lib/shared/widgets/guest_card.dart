import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';

class GuestCard extends StatelessWidget {
  const GuestCard({super.key, required this.invitado, required this.onTap});

  final InvitadoModel invitado;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final initial = invitado.nombreCompleto.isNotEmpty ? invitado.nombreCompleto[0].toUpperCase() : '?';

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const TricolorAccent(),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 24,
                        backgroundColor: VenezuelaColors.blueContainer,
                        foregroundColor: VenezuelaColors.blue,
                        child: Text(initial, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              invitado.nombreCompleto,
                              style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              invitado.cedula ?? 'Sin cédula',
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                            ),
                            if (invitado.telefono != null && invitado.telefono!.isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Row(
                                children: [
                                  Icon(Icons.phone, size: 12, color: Theme.of(context).colorScheme.onSurfaceVariant),
                                  const SizedBox(width: 4),
                                  Text(invitado.telefono!, style: Theme.of(context).textTheme.bodySmall),
                                ],
                              ),
                            ],
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right, color: VenezuelaColors.blue),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
