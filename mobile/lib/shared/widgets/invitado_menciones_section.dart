import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

class InvitadoMencionesSection extends StatefulWidget {
  const InvitadoMencionesSection({
    super.key,
    required this.catalog,
    required this.invitado,
    required this.onSave,
    this.saving = false,
    this.isOnline = true,
  });

  final MencionesCatalogo? catalog;
  final InvitadoModel invitado;
  final Future<void> Function({
    required List<String> ayudas,
    required List<String> salud,
    required List<String> tramites,
    String? nota,
  }) onSave;
  final bool saving;
  final bool isOnline;

  @override
  State<InvitadoMencionesSection> createState() => _InvitadoMencionesSectionState();
}

class _InvitadoMencionesSectionState extends State<InvitadoMencionesSection> {
  late Set<String> _ayudas;
  late Set<String> _salud;
  late Set<String> _tramites;
  late TextEditingController _notaController;
  bool _expanded = false;

  @override
  void initState() {
    super.initState();
    _syncFromInvitado(widget.invitado);
  }

  @override
  void didUpdateWidget(covariant InvitadoMencionesSection oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.invitado.id != widget.invitado.id ||
        oldWidget.invitado.mencionesAyudas != widget.invitado.mencionesAyudas ||
        oldWidget.invitado.mencionesSalud != widget.invitado.mencionesSalud ||
        oldWidget.invitado.mencionesTramites != widget.invitado.mencionesTramites ||
        oldWidget.invitado.mencionesNota != widget.invitado.mencionesNota) {
      _syncFromInvitado(widget.invitado);
    }
  }

  void _syncFromInvitado(InvitadoModel invitado) {
    _ayudas = invitado.mencionesAyudas.toSet();
    _salud = invitado.mencionesSalud.toSet();
    _tramites = invitado.mencionesTramites.toSet();
    _notaController = TextEditingController(text: invitado.mencionesNota ?? '');
  }

  @override
  void dispose() {
    _notaController.dispose();
    super.dispose();
  }

  String get _subtitle {
    final labels = widget.invitado.resolveMencionesLabels(widget.catalog);
    if (labels.isEmpty) return 'Opcional · sin menciones registradas';
    final parts = [
      ...labels.ayudas.map((e) => e.label),
      ...labels.salud.map((e) => e.label),
      ...labels.tramites.map((e) => e.label),
    ];
    if (parts.isEmpty && labels.nota != null && labels.nota!.trim().isNotEmpty) {
      return labels.nota!.trim();
    }
    return parts.join(', ');
  }

  Future<void> _guardar() async {
    await widget.onSave(
      ayudas: _ayudas.toList(),
      salud: _salud.toList(),
      tramites: _tramites.toList(),
      nota: _notaController.text.trim().isEmpty ? null : _notaController.text.trim(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final catalog = widget.catalog;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: _expanded,
          onExpansionChanged: (value) => setState(() => _expanded = value),
          leading: Icon(Icons.label_outline, color: VenezuelaColors.blue),
          title: const Text('Menciones opcionales'),
          subtitle: Text(
            _subtitle,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.bodySmall,
          ),
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'Etiquetado informativo. No genera requerimientos ni trámites formales.',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                  ),
                  if (!widget.isOnline) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Sin conexión: se guardará en la cola de sincronización.',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: VenezuelaColors.onYellowContainer,
                          ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  if (catalog == null)
                    Text(
                      'Catálogo no disponible offline. Conecte para cargar opciones.',
                      style: Theme.of(context).textTheme.bodySmall,
                    )
                  else ...[
                    _MencionesGroup(
                      title: 'Ayudas',
                      icon: Icons.restaurant_outlined,
                      options: catalog.ayudas,
                      selected: _ayudas,
                      onChanged: (value, selected) {
                        setState(() {
                          if (selected) {
                            _ayudas.add(value);
                          } else {
                            _ayudas.remove(value);
                          }
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    _MencionesGroup(
                      title: 'Salud',
                      icon: Icons.medical_services_outlined,
                      options: catalog.salud,
                      selected: _salud,
                      onChanged: (value, selected) {
                        setState(() {
                          if (selected) {
                            _salud.add(value);
                          } else {
                            _salud.remove(value);
                          }
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    _MencionesGroup(
                      title: 'Trámites documentales',
                      icon: Icons.description_outlined,
                      options: catalog.tramites,
                      selected: _tramites,
                      onChanged: (value, selected) {
                        setState(() {
                          if (selected) {
                            _tramites.add(value);
                          } else {
                            _tramites.remove(value);
                          }
                        });
                      },
                    ),
                  ],
                  const SizedBox(height: 12),
                  M3TextField(
                    controller: _notaController,
                    label: 'Nota breve (opcional)',
                    hint: 'Máximo 500 caracteres',
                    icon: Icons.notes_outlined,
                    maxLines: 3,
                    enabled: !widget.saving,
                  ),
                  const SizedBox(height: 12),
                  FilledButton.icon(
                    onPressed: widget.saving || catalog == null ? null : _guardar,
                    icon: widget.saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : Icon(widget.isOnline ? Icons.save_outlined : Icons.cloud_upload_outlined),
                    label: Text(widget.isOnline ? 'Guardar menciones' : 'Guardar offline'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MencionesGroup extends StatelessWidget {
  const _MencionesGroup({
    required this.title,
    required this.icon,
    required this.options,
    required this.selected,
    required this.onChanged,
  });

  final String title;
  final IconData icon;
  final List<InvitadoMencionOption> options;
  final Set<String> selected;
  final void Function(String value, bool selected) onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 18, color: VenezuelaColors.blue),
            const SizedBox(width: 6),
            Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
          ],
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 4,
          children: [
            for (final option in options)
              FilterChip(
                label: Text(option.label),
                selected: selected.contains(option.value),
                onSelected: (value) => onChanged(option.value, value),
                selectedColor: VenezuelaColors.blueContainer,
                checkmarkColor: VenezuelaColors.blue,
              ),
          ],
        ),
      ],
    );
  }
}
