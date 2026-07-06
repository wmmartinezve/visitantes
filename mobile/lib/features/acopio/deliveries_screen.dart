import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/core/utils/geo_links.dart';
import 'package:visitantes_mobile/core/utils/map_launcher.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/delivery_card.dart';
import 'package:visitantes_mobile/shared/widgets/pending_queue_panel.dart';

class DeliveriesScreen extends StatefulWidget {
  const DeliveriesScreen({
    super.key,
    required this.fieldApi,
    required this.catalog,
    required this.sync,
  });

  final FieldApi fieldApi;
  final CatalogService catalog;
  final SyncService sync;

  @override
  State<DeliveriesScreen> createState() => _DeliveriesScreenState();
}

class _DeliveriesScreenState extends State<DeliveriesScreen> {
  List<RequerimientoModel> _entregas = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _refreshAll();
  }

  Future<void> _loadEntregas() async {
    final list = await widget.fieldApi.fetchEntregas();
    if (!mounted) return;
    setState(() {
      _entregas = list;
      _loading = false;
    });
  }

  Future<void> _refreshAll() async {
    setState(() => _loading = true);
    final online = await widget.catalog.isOnline;
    if (online) {
      await widget.sync.refreshAll();
    }
    await _loadEntregas();
  }

  Future<void> _openNavigate(RequerimientoModel entrega) async {
    await MapLauncher.open(
      context,
      GeoLinks.rutaUrlFor(entrega),
      errorMessage: 'No se pudo abrir la ruta. Configure la ubicación GPS de su centro en Inicio.',
    );
  }

  Future<void> _openRefugio(RequerimientoModel entrega) async {
    await MapLauncher.open(
      context,
      GeoLinks.refugioUrlFor(entrega),
      errorMessage: 'No se pudo abrir el mapa del refugio.',
    );
  }

  Future<void> _marcarEntregado(RequerimientoModel entrega) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        icon: const Icon(Icons.local_shipping, color: VenezuelaColors.blue),
        title: const Text('Confirmar entrega'),
        content: Text('¿Descontar del inventario y marcar como entregado?\n\n${entrega.itemSolicitado} · ${entrega.cantidad} u.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
            child: const Text('Confirmar'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      final online = await widget.catalog.isOnline;
      if (online) {
        await widget.fieldApi.marcarEntregadoOnline(entrega.id);
        await widget.sync.refreshAll(syncQueue: false);
      } else {
        await widget.sync.enqueue(
          'entrega.marcar',
          {'requerimiento_id': entrega.id},
          syncImmediately: false,
        );
        widget.sync.scheduleAutoSync();
      }
      await _loadEntregas();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(online ? 'Entrega registrada.' : 'Entrega guardada offline — sincronice cuando tenga red.'),
          backgroundColor: VenezuelaColors.blue,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    final pendingEntregas = widget.sync.listPending().where((i) => i['type'] == 'entrega.marcar').length;

    return RefreshIndicator(
      onRefresh: _refreshAll,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
        children: [
          SectionHeader(
            title: 'Entregas asignadas',
            action: IconButton(
              onPressed: _refreshAll,
              tooltip: 'Sincronizar y actualizar',
              icon: const Icon(Icons.sync, color: VenezuelaColors.blue),
            ),
          ),
          if (pendingEntregas > 0) ...[
            PendingQueuePanel(sync: widget.sync, onSync: _refreshAll, compact: true),
            const SizedBox(height: 12),
          ],
          if (_entregas.isEmpty)
            const EmptyState(
              icon: Icons.local_shipping_outlined,
              title: 'No hay entregas pendientes',
              message: 'Cuando se asignen requerimientos a su centro, aparecerán aquí. Deslice hacia abajo para actualizar.',
            )
          else ...[
            Text(
              '${_entregas.length} entrega(s) por completar',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant),
            ),
            const SizedBox(height: 8),
            ..._entregas.map(
              (e) => DeliveryCard(
                entrega: e,
                onNavigate: () => _openNavigate(e),
                onViewRefugio: () => _openRefugio(e),
                onDeliver: () => _marcarEntregado(e),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
