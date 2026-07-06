import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Captura de foto testigo de ingreso con miniatura previa.
class WitnessPhotoCapture extends StatelessWidget {
  const WitnessPhotoCapture({
    super.key,
    required this.previewBytes,
    required this.onCapture,
    this.onRemove,
    this.fileName,
    this.sizeLabel,
    this.loading = false,
  });

  final Uint8List? previewBytes;
  final VoidCallback onCapture;
  final VoidCallback? onRemove;
  final String? fileName;
  final String? sizeLabel;
  final bool loading;

  static const _thumbSize = 96.0;

  @override
  Widget build(BuildContext context) {
    final hasPhoto = previewBytes != null && previewBytes!.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: loading ? null : onCapture,
            borderRadius: BorderRadius.circular(16),
            child: Ink(
              decoration: BoxDecoration(
                color: hasPhoto ? Colors.white : VenezuelaColors.surfaceContainer,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: hasPhoto ? VenezuelaColors.blue.withValues(alpha: 0.4) : VenezuelaColors.blue.withValues(alpha: 0.2),
                  width: hasPhoto ? 2 : 1,
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: hasPhoto ? _buildWithThumbnail(context) : _buildEmpty(context),
              ),
            ),
          ),
        ),
        if (hasPhoto && onRemove != null) ...[
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton.icon(
                onPressed: loading ? null : onCapture,
                icon: const Icon(Icons.cameraswitch_outlined, size: 18),
                label: const Text('Retomar'),
              ),
              TextButton.icon(
                onPressed: loading ? null : onRemove,
                icon: const Icon(Icons.delete_outline, size: 18, color: VenezuelaColors.red),
                label: const Text('Quitar', style: TextStyle(color: VenezuelaColors.red)),
              ),
            ],
          ),
        ],
      ],
    );
  }

  Widget _buildEmpty(BuildContext context) {
    return Row(
      children: [
        Container(
          width: _thumbSize,
          height: _thumbSize,
          decoration: BoxDecoration(
            color: VenezuelaColors.yellowContainer,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: VenezuelaColors.blue.withValues(alpha: 0.15), width: 1.5, strokeAlign: BorderSide.strokeAlignInside),
          ),
          child: loading
              ? const Center(child: SizedBox(width: 28, height: 28, child: CircularProgressIndicator(strokeWidth: 2)))
              : const Icon(Icons.add_a_photo_outlined, size: 36, color: VenezuelaColors.onYellowContainer),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.verified_user_outlined, size: 16, color: VenezuelaColors.blue.withValues(alpha: 0.8)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'Foto testigo de ingreso',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                'Toque para abrir la cámara',
                style: Theme.of(context).textTheme.bodySmall,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              Text(
                'Opcional · evidencia visual del Invitado',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant.withValues(alpha: 0.8),
                    ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildWithThumbnail(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Stack(
          clipBehavior: Clip.none,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: Image.memory(
                previewBytes!,
                width: _thumbSize,
                height: _thumbSize,
                fit: BoxFit.cover,
                gaplessPlayback: true,
                errorBuilder: (context, error, stackTrace) => Container(
                  width: _thumbSize,
                  height: _thumbSize,
                  color: VenezuelaColors.surfaceContainer,
                  child: const Icon(Icons.broken_image_outlined),
                ),
              ),
            ),
            Positioned(
              right: -4,
              bottom: -4,
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: const BoxDecoration(
                  color: Color(0xFF1B5E20),
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 4, offset: Offset(0, 1))],
                ),
                child: const Icon(Icons.check, size: 14, color: Colors.white),
              ),
            ),
          ],
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: VenezuelaColors.blueContainer,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  'Testigo capturado',
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: VenezuelaColors.blue),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                fileName ?? 'Foto de ingreso',
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
              if (sizeLabel != null) ...[
                const SizedBox(height: 4),
                Text(
                  sizeLabel!,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: VenezuelaColors.blue,
                        fontWeight: FontWeight.w500,
                      ),
                ),
              ],
              const SizedBox(height: 4),
              Text(
                'Toque la miniatura para retomar',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
      ],
    );
  }
}
