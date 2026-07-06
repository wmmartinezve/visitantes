import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class InventoryTile extends StatelessWidget {
  const InventoryTile({
    super.key,
    required this.nombre,
    required this.unidad,
    required this.cantidad,
    this.isEditing = false,
    this.editController,
    this.onEdit,
    this.onSave,
    this.onCancel,
  });

  final String nombre;
  final String unidad;
  final int cantidad;
  final bool isEditing;
  final TextEditingController? editController;
  final VoidCallback? onEdit;
  final VoidCallback? onSave;
  final VoidCallback? onCancel;

  @override
  Widget build(BuildContext context) {
    final lowStock = cantidad <= 5;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: lowStock ? VenezuelaColors.redContainer : VenezuelaColors.blueContainer,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                Icons.inventory_2,
                color: lowStock ? VenezuelaColors.red : VenezuelaColors.blue,
                size: 22,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(nombre, style: const TextStyle(fontWeight: FontWeight.w500)),
                  const SizedBox(height: 2),
                  Text(unidad, style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
            if (isEditing && editController != null) ...[
              SizedBox(
                width: 72,
                child: TextField(
                  controller: editController,
                  keyboardType: TextInputType.number,
                  textAlign: TextAlign.center,
                  decoration: InputDecoration(
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
              IconButton(
                onPressed: onSave,
                icon: const Icon(Icons.check, color: VenezuelaColors.blue),
                tooltip: 'Guardar',
              ),
              IconButton(
                onPressed: onCancel,
                icon: Icon(Icons.close, color: Theme.of(context).colorScheme.onSurfaceVariant),
                tooltip: 'Cancelar',
              ),
            ] else ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: lowStock ? VenezuelaColors.yellowContainer : VenezuelaColors.surfaceContainer,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '$cantidad',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: lowStock ? VenezuelaColors.onYellowContainer : VenezuelaColors.blue,
                  ),
                ),
              ),
              if (onEdit != null)
                IconButton(
                  onPressed: onEdit,
                  icon: const Icon(Icons.edit_outlined, size: 20, color: VenezuelaColors.blue),
                  tooltip: 'Editar cantidad',
                ),
            ],
          ],
        ),
      ),
    );
  }
}
