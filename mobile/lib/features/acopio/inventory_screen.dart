import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/insumo_picker.dart';
import 'package:visitantes_mobile/shared/widgets/inventory_tile.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

class InventoryScreen extends StatefulWidget {
  const InventoryScreen({super.key, required this.catalog, required this.sync});

  final CatalogService catalog;
  final SyncService sync;

  @override
  State<InventoryScreen> createState() => _InventoryScreenState();
}

class _InventoryScreenState extends State<InventoryScreen> {
  String? _categoria;
  String? _subcategoria;
  final _cantidad = TextEditingController(text: '0');
  final _unidad = TextEditingController(text: 'unidad');
  final _editCantidad = TextEditingController();
  int? _editandoId;
  bool _saving = false;

  List<Map<String, dynamic>> get _items {
    final raw = widget.catalog.cachedCatalog?['inventario_local'] as List<dynamic>? ?? [];
    final items = raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();

    for (final pending in widget.sync.listPending()) {
      if (pending['type'] != 'inventario.update_cantidad') continue;
      final payload = pending['payload'];
      if (payload is! Map) continue;
      final id = payload['inventario_id'];
      final qty = payload['cantidad'];
      if (id is! int && id is! num) continue;
      final idx = items.indexWhere((e) => e['id'] == id);
      if (idx >= 0 && qty is num) {
        items[idx] = {...items[idx], 'cantidad': qty.toInt()};
      }
    }

    return items;
  }

  List<String> get _unidades {
    final raw = widget.catalog.cachedCatalog?['unidades_medida'] as List<dynamic>? ?? [];
    return raw.map((e) => e.toString()).toList();
  }

  Map<String, dynamic> get _catalogo => insumosCatalogoFromCache(widget.catalog.cachedCatalog);

  @override
  void dispose() {
    _cantidad.dispose();
    _unidad.dispose();
    _editCantidad.dispose();
    super.dispose();
  }

  Future<void> _refreshAll() async {
    setState(() => _saving = true);
    try {
      final online = await widget.catalog.isOnline;
      if (online) {
        await widget.sync.refreshAll();
      }
      if (mounted) setState(() {});
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _iniciarEdicion(Map<String, dynamic> item) {
    final id = item['id'];
    if (id is! int && id is! num) return;
    setState(() {
      _editandoId = id is int ? id : id.toInt();
      _editCantidad.text = '${item['cantidad'] ?? 0}';
    });
  }

  void _cancelarEdicion() {
    setState(() {
      _editandoId = null;
      _editCantidad.clear();
    });
  }

  Future<void> _guardarCantidad(int inventarioId) async {
    final qty = int.tryParse(_editCantidad.text.trim());
    if (qty == null || qty < 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Indique una cantidad válida.')),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final online = await widget.catalog.isOnline;

      await widget.sync.enqueueInventarioCantidadUpdate(
        inventarioId: inventarioId,
        cantidad: qty,
      );
      await widget.catalog.patchInventarioLocalCantidad(inventarioId, qty);

      if (online) {
        await widget.sync.refreshAll();
      }

      _cancelarEdicion();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(online ? 'Cantidad actualizada.' : 'Cambio guardado offline.'),
          backgroundColor: VenezuelaColors.blue,
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _addItem() async {
    if (_categoria == null || _categoria!.isEmpty || _subcategoria == null || _subcategoria!.isEmpty) {
      return;
    }

    setState(() => _saving = true);
    try {
      final payload = {
        'categoria': _categoria!,
        'subcategoria': _subcategoria!,
        'cantidad': int.tryParse(_cantidad.text.trim()) ?? 0,
        'unidad_medida': _unidad.text.trim().isEmpty ? 'unidad' : _unidad.text.trim(),
      };

      final online = await widget.catalog.isOnline;
      await widget.sync.enqueue('inventario.create', payload);

      if (online) {
        await widget.sync.refreshAll();
      }

      setState(() {
        _categoria = null;
        _subcategoria = null;
      });
      _cantidad.text = '0';
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(online ? 'Ítem registrado.' : 'Ítem guardado offline.'),
          backgroundColor: VenezuelaColors.blue,
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Widget _buildAutocompleteField({
    required TextEditingController syncController,
    required String label,
    required IconData icon,
    required Iterable<String> options,
    bool includeSpacing = true,
  }) {
    return Padding(
      padding: EdgeInsets.only(bottom: includeSpacing ? M3InputStyles.fieldSpacing : 0),
      child: Autocomplete<String>(
        optionsBuilder: (value) => options.where((o) => o.toLowerCase().contains(value.text.toLowerCase())),
        onSelected: (v) => syncController.text = v,
        fieldViewBuilder: (context, controller, focusNode, onSubmitted) {
          controller.text = syncController.text;
          controller.addListener(() => syncController.text = controller.text);
          return TextField(
            controller: controller,
            focusNode: focusNode,
            decoration: M3InputStyles.decoration(context: context, label: label, icon: icon),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.sync,
      builder: (context, _) => RefreshIndicator(
        onRefresh: _refreshAll,
        child: ListView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            FormSectionCard(
              title: 'Agregar ítem',
              icon: M3FieldIcons.inventory,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  InsumoPicker(
                    key: ValueKey('inv-picker-$_categoria-$_subcategoria'),
                    catalogo: _catalogo,
                    categoria: _categoria,
                    subcategoria: _subcategoria,
                    onChanged: (cat, sub) => setState(() {
                      _categoria = cat;
                      _subcategoria = sub;
                    }),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(bottom: M3InputStyles.fieldSpacing),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: M3TextFieldRaw(
                            controller: _cantidad,
                            label: 'Cantidad',
                            icon: M3FieldIcons.quantity,
                            keyboardType: TextInputType.number,
                            includeSpacing: false,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildAutocompleteField(
                            syncController: _unidad,
                            label: 'Unidad',
                            icon: M3FieldIcons.unit,
                            options: _unidades,
                            includeSpacing: false,
                          ),
                        ),
                      ],
                    ),
                  ),
                  FilledButton.icon(
                    onPressed: _saving ? null : _addItem,
                    icon: _saving
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Icon(Icons.add_outlined),
                    label: const Text('Agregar al inventario'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            SectionHeader(
              title: 'Stock en caché (${_items.length})',
              action: IconButton(
                onPressed: _saving ? null : _refreshAll,
                tooltip: 'Sincronizar y actualizar',
                icon: const Icon(Icons.sync, color: VenezuelaColors.blue),
              ),
            ),
            if (_items.isEmpty)
              const EmptyState(
                icon: Icons.inventory_2_outlined,
                title: 'Sin ítems en caché',
                message: 'Conecte a internet para descargar el inventario o agregue ítems manualmente.',
              )
            else
              ..._items.map((item) {
                final id = item['id'];
                final inventarioId = id is int ? id : (id is num ? id.toInt() : null);
                final editing = inventarioId != null && _editandoId == inventarioId;

                return InventoryTile(
                  nombre: item['subcategoria'] as String? ?? item['item_nombre'] as String? ?? '—',
                  unidad: [item['categoria'], item['unidad_medida']].whereType<String>().where((s) => s.isNotEmpty).join(' · '),
                  cantidad: item['cantidad'] as int? ?? 0,
                  isEditing: editing,
                  editController: editing ? _editCantidad : null,
                  onEdit: inventarioId != null && !_saving ? () => _iniciarEdicion(item) : null,
                  onSave: inventarioId != null ? () => _guardarCantidad(inventarioId) : null,
                  onCancel: _cancelarEdicion,
                );
              }),
          ],
        ),
      ),
    );
  }
}
