import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';

class RequirementCard extends StatelessWidget {
  const RequirementCard({super.key, required this.requerimiento});

  final RequerimientoModel requerimiento;

  @override
  Widget build(BuildContext context) {
    final r = requerimiento;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      clipBehavior: Clip.antiAlias,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const TricolorAccent(width: 3),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(r.itemSolicitado, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
                          const SizedBox(height: 4),
                          Text('${r.cantidad} u. · ${r.invitadoNombre ?? 'Invitado'}'),
                          if (r.centroAcopioNombre != null) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.warehouse, size: 14, color: VenezuelaColors.blue),
                                const SizedBox(width: 4),
                                Expanded(child: Text('Centro: ${r.centroAcopioNombre}', style: Theme.of(context).textTheme.bodySmall)),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    StatusChip(label: r.estatusLabel, estatus: r.estatus),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
