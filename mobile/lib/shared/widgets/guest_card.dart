import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/invitado_avatar.dart';

class GuestCard extends StatelessWidget {
  const GuestCard({super.key, required this.invitado, required this.onTap});

  final InvitadoModel invitado;
  final VoidCallback onTap;

  String _subtitle() {
    final parts = <String>[
      invitado.rolEnFamiliaLabel,
      if (invitado.edadLabel != null) invitado.edadLabel!,
      invitado.cedula ?? 'Sin cédula',
    ];

    return parts.join(' · ');
  }

  String? get _hogarLabel {
    final codigo = invitado.hogarCodigo;
    if (codigo == null || codigo.isEmpty) return null;
    return 'Hogar: $codigo';
  }

  @override
  Widget build(BuildContext context) {
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
                      InvitadoAvatar(
                        nombreCompleto: invitado.nombreCompleto,
                        fotoUrl: invitado.fotoUrl,
                        radius: 24,
                        backgroundColor: invitado.esJefeFamilia ? VenezuelaColors.blueContainer : VenezuelaColors.yellowContainer,
                        foregroundColor: invitado.esJefeFamilia ? VenezuelaColors.blue : VenezuelaColors.onYellowContainer,
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
                              _subtitle(),
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
                            ),
                            if (_hogarLabel != null) ...[
                              const SizedBox(height: 2),
                              Text(
                                _hogarLabel!,
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: VenezuelaColors.blue,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                            if (invitado.registradoEl != null) ...[
                              const SizedBox(height: 2),
                              Text(
                                'Registro: ${invitado.registradoEl}',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                            if (invitado.telefono != null && invitado.telefono!.isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Row(
                                children: [
                                  Icon(Icons.phone, size: 12, color: Theme.of(context).colorScheme.onSurfaceVariant),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(invitado.telefono!, style: Theme.of(context).textTheme.bodySmall, overflow: TextOverflow.ellipsis),
                                  ),
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
