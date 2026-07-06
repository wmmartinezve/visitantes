import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/insumo_picker.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';
import 'package:visitantes_mobile/shared/widgets/requirement_card.dart';

class GuestDetailScreen extends StatefulWidget {
  const GuestDetailScreen({
    super.key,
    required this.invitadoId,
    required this.fieldApi,
    this.catalog,
    this.sync,
  });

  final int invitadoId;
  final FieldApi fieldApi;
  final CatalogService? catalog;
  final SyncService? sync;

  @override
  State<GuestDetailScreen> createState() => _GuestDetailScreenState();
}

class _GuestDetailScreenState extends State<GuestDetailScreen> {
  InvitadoModel? _invitado;
  bool _loading = true;
  String? _categoria;
  String? _subcategoria;
  final _cantidad = TextEditingController(text: '1');
  bool _saving = false;

  CatalogService get _catalog => widget.catalog ?? CatalogService();
  SyncService get _sync => widget.sync ?? SyncService();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _cantidad.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final inv = await widget.fieldApi.fetchInvitado(widget.invitadoId);
    if (!mounted) return;
    setState(() {
      _invitado = inv;
      _loading = false;
    });
  }

  Future<void> _addRequerimiento() async {
    if (_categoria == null || _categoria!.isEmpty || _subcategoria == null || _subcategoria!.isEmpty) {
      return;
    }
    setState(() => _saving = true);

    final payload = {
      'invitado_id': widget.invitadoId,
      'categoria': _categoria!,
      'subcategoria': _subcategoria!,
      'cantidad': int.tryParse(_cantidad.text.trim()) ?? 1,
    };

    try {
      final online = await _catalog.isOnline;
      if (online) {
        await widget.fieldApi.createRequerimientoOnline(
          invitadoId: widget.invitadoId,
          categoria: _categoria!,
          subcategoria: _subcategoria!,
          cantidad: payload['cantidad'] as int,
        );
      } else {
        await _sync.enqueue('requerimiento.create', payload);
      }

      setState(() {
        _categoria = null;
        _subcategoria = null;
      });
      _cantidad.text = '1';
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(online ? 'Requerimiento registrado.' : 'Requerimiento guardado offline.'),
          backgroundColor: VenezuelaColors.blue,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final inv = _invitado;
    final initial = inv != null && inv.nombreCompleto.isNotEmpty ? inv.nombreCompleto[0].toUpperCase() : '?';

    return BrandedDetailScaffold(
      title: inv?.nombreCompleto ?? 'Invitado',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (inv != null) ...[
                    Card(
                      clipBehavior: Clip.antiAlias,
                      child: IntrinsicHeight(
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const TricolorAccent(),
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.all(16),
                                child: Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 32,
                                      backgroundColor: VenezuelaColors.blueContainer,
                                      foregroundColor: VenezuelaColors.blue,
                                      child: Text(initial, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w700)),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(inv.nombreCompleto, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                                          const SizedBox(height: 4),
                                          _DetailLine(icon: Icons.badge_outlined, text: inv.cedula ?? 'Sin cédula'),
                                          _DetailLine(icon: Icons.phone_outlined, text: inv.telefono ?? 'Sin teléfono'),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    if (inv.miembrosFamilia.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      SectionHeader(title: 'Núcleo familiar (${inv.miembrosFamilia.length})'),
                      ...inv.miembrosFamilia.map(
                        (m) => Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: VenezuelaColors.yellowContainer,
                              foregroundColor: VenezuelaColors.onYellowContainer,
                              child: Text(m.nombreCompleto.isNotEmpty ? m.nombreCompleto[0].toUpperCase() : '?'),
                            ),
                            title: Text(m.nombreCompleto),
                            subtitle: Text(
                              [if (m.parentesco != null && m.parentesco!.isNotEmpty) m.parentesco, m.cedula ?? 'Sin cédula']
                                  .whereType<String>()
                                  .join(' · '),
                            ),
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    FormSectionCard(
                      title: 'Nuevo requerimiento',
                      icon: M3FieldIcons.item,
                      child: Column(
                        children: [
                          InsumoPicker(
                            key: ValueKey('req-picker-$_categoria-$_subcategoria'),
                            catalogo: insumosCatalogoFromCache(_catalog.cachedCatalog),
                            categoria: _categoria,
                            subcategoria: _subcategoria,
                            onChanged: (cat, sub) => setState(() {
                              _categoria = cat;
                              _subcategoria = sub;
                            }),
                          ),
                          M3TextFieldRaw(
                            controller: _cantidad,
                            label: 'Cantidad',
                            icon: M3FieldIcons.quantity,
                            keyboardType: TextInputType.number,
                          ),
                          const SizedBox(height: 4),
                          FilledButton.icon(
                            onPressed: _saving ? null : _addRequerimiento,
                            icon: const Icon(Icons.add),
                            label: const Text('Agregar requerimiento'),
                            style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
                          ),
                        ],
                      ),
                    ),
                    if (inv.requerimientos.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      SectionHeader(title: 'Requerimientos (${inv.requerimientos.length})'),
                      ...inv.requerimientos.map((r) => RequirementCard(requerimiento: r)),
                    ],
                  ],
                ],
              ),
            ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  const _DetailLine({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 2),
      child: Row(
        children: [
          Icon(icon, size: 14, color: Theme.of(context).colorScheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Expanded(child: Text(text, style: Theme.of(context).textTheme.bodySmall)),
        ],
      ),
    );
  }
}
