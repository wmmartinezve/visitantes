import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/core/utils/geo_links.dart';
import 'package:visitantes_mobile/shared/widgets/venezuela_tricolor_bar.dart';

class DeliveryCard extends StatelessWidget {
  const DeliveryCard({
    super.key,
    required this.entrega,
    required this.onNavigate,
    required this.onViewRefugio,
    required this.onDeliver,
  });

  final RequerimientoModel entrega;
  final VoidCallback onNavigate;
  final VoidCallback onViewRefugio;
  final VoidCallback onDeliver;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final canNavigate = GeoLinks.canNavigate(entrega);
    final canViewRefugio = GeoLinks.canViewRefugio(entrega);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const VenezuelaTricolorBar(height: 4),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: VenezuelaColors.yellowContainer,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.inventory_2, color: VenezuelaColors.onYellowContainer),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(entrega.itemSolicitado, style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
                          const SizedBox(height: 4),
                          Text('${entrega.cantidad} u. · ${entrega.invitadoNombre ?? 'Invitado'}'),
                        ],
                      ),
                    ),
                    if (entrega.distanciaKm != null)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: VenezuelaColors.blueContainer,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.route, size: 14, color: VenezuelaColors.blue),
                            const SizedBox(width: 4),
                            Text(
                              '${entrega.distanciaKm!.toStringAsFixed(1)} km',
                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: VenezuelaColors.blue),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
                if (entrega.refugioNombre != null) ...[
                  const SizedBox(height: 12),
                  _InfoRow(icon: Icons.home_work, label: 'Refugio', value: entrega.refugioNombre!),
                ],
                if (entrega.refugioDireccion != null) ...[
                  const SizedBox(height: 6),
                  _InfoRow(icon: Icons.location_on, label: 'Dirección', value: entrega.refugioDireccion!),
                ],
                if (canNavigate || canViewRefugio) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      if (canNavigate)
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: onNavigate,
                            icon: const Icon(Icons.directions, size: 18),
                            label: const Text('Cómo llegar'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: VenezuelaColors.blue,
                              side: const BorderSide(color: VenezuelaColors.blue),
                            ),
                          ),
                        ),
                      if (canNavigate && canViewRefugio) const SizedBox(width: 8),
                      if (canViewRefugio)
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: onViewRefugio,
                            icon: const Icon(Icons.map, size: 18),
                            label: const Text('Ver refugio'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: VenezuelaColors.blue,
                              side: const BorderSide(color: VenezuelaColors.blue),
                            ),
                          ),
                        ),
                    ],
                  ),
                ],
                const SizedBox(height: 12),
                FilledButton.icon(
                  onPressed: onDeliver,
                  icon: const Icon(Icons.done_all),
                  label: const Text('Marcar entregado'),
                  style: FilledButton.styleFrom(
                    backgroundColor: VenezuelaColors.red,
                    foregroundColor: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: VenezuelaColors.red),
        const SizedBox(width: 6),
        Expanded(
          child: RichText(
            text: TextSpan(
              style: Theme.of(context).textTheme.bodySmall,
              children: [
                TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w600)),
                TextSpan(text: value),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
