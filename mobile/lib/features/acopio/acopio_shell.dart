import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/acopio/centro_geolocalizacion_card.dart';
import 'package:visitantes_mobile/features/acopio/deliveries_screen.dart';
import 'package:visitantes_mobile/features/acopio/inventory_screen.dart';
import 'package:visitantes_mobile/shared/widgets/app_scaffold.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/pending_queue_panel.dart';

class AcopioShell extends StatefulWidget {
  const AcopioShell({
    super.key,
    required this.user,
    required this.catalog,
    required this.sync,
    required this.onLogout,
    required this.onUserUpdated,
  });

  final MobileUser user;
  final CatalogService catalog;
  final SyncService sync;
  final VoidCallback onLogout;
  final ValueChanged<MobileUser> onUserUpdated;

  @override
  State<AcopioShell> createState() => _AcopioShellState();
}

class _AcopioShellState extends State<AcopioShell> {
  int _index = 0;
  int _refreshTick = 0;
  late MobileUser _user = widget.user;
  late final FieldApi _fieldApi = FieldApi(catalogService: widget.catalog);

  @override
  void didUpdateWidget(covariant AcopioShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.user.id != widget.user.id) {
      _user = widget.user;
    }
  }

  void _goTo(int index) => setState(() => _index = index);

  void _bumpRefresh() => setState(() => _refreshTick++);

  void _handleUserUpdated(MobileUser user) {
    setState(() => _user = user);
    widget.onUserUpdated(user);
  }

  Future<void> _syncFromHome() async {
    await widget.sync.refreshAll();
    _bumpRefresh();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      _HomeTab(
        user: _user,
        fieldApi: _fieldApi,
        catalog: widget.catalog,
        sync: widget.sync,
        onNavigate: _goTo,
        onSync: _syncFromHome,
        onUserUpdated: _handleUserUpdated,
      ),
      InventoryScreen(key: ValueKey('inv-$_refreshTick'), catalog: widget.catalog, sync: widget.sync),
      DeliveriesScreen(
        key: ValueKey('del-$_refreshTick'),
        fieldApi: _fieldApi,
        catalog: widget.catalog,
        sync: widget.sync,
      ),
    ];

    return AppScaffold(
      title: _user.name,
      subtitle: 'Centro: ${_user.centroNombre ?? '—'}',
      catalog: widget.catalog,
      sync: widget.sync,
      onLogout: widget.onLogout,
      onRefreshComplete: _bumpRefresh,
      body: pages[_index],
      bottomNav: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _goTo,
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home), label: 'Inicio'),
          NavigationDestination(icon: Icon(Icons.inventory_2), label: 'Inventario'),
          NavigationDestination(icon: Icon(Icons.local_shipping), label: 'Entregas'),
        ],
      ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab({
    required this.user,
    required this.fieldApi,
    required this.catalog,
    required this.sync,
    required this.onNavigate,
    required this.onSync,
    required this.onUserUpdated,
  });

  final MobileUser user;
  final FieldApi fieldApi;
  final CatalogService catalog;
  final SyncService sync;
  final ValueChanged<int> onNavigate;
  final Future<void> Function() onSync;
  final ValueChanged<MobileUser> onUserUpdated;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: sync,
      builder: (context, _) {
        final inventario = catalog.cachedCatalog?['inventario_local'] as List<dynamic>? ?? [];
        final pending = sync.pendingCount;
        final entregasPendientes = sync.listPending().where((i) => i['type'] == 'entrega.marcar').length;

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            StatCard(
              icon: Icons.warehouse,
              title: 'Centro de acopio',
              value: user.centroNombre ?? '—',
              accent: VenezuelaColors.blue,
            ),
            const SizedBox(height: 12),
            CentroGeolocalizacionCard(
              user: user,
              fieldApi: fieldApi,
              catalog: catalog,
              onUpdated: onUserUpdated,
            ),
            const SizedBox(height: 10),
            StatCard(
              icon: Icons.inventory,
              title: 'Ítems en caché',
              value: '${inventario.length}',
              subtitle: pending > 0 ? '$pending pendiente(s) de sync' : 'Inventario en caché local',
              accent: const Color(0xFF9A7200),
            ),
            if (entregasPendientes > 0) ...[
              const SizedBox(height: 10),
              StatCard(
                icon: Icons.local_shipping,
                title: 'Entregas por sincronizar',
                value: '$entregasPendientes',
                subtitle: 'Marcadas offline, pendientes de envío',
                accent: VenezuelaColors.red,
                onTap: () => onNavigate(2),
              ),
            ],
            const SizedBox(height: 20),
            SectionHeader(
              title: 'Cola de sincronización',
              action: IconButton(
                onPressed: onSync,
                tooltip: 'Sincronizar ahora',
                icon: const Icon(Icons.sync, color: VenezuelaColors.blue),
              ),
            ),
            PendingQueuePanel(sync: sync, onSync: onSync),
            const SizedBox(height: 20),
            const SectionHeader(title: 'Acciones rápidas'),
            Row(
              children: [
                Expanded(child: QuickActionTile(icon: Icons.inventory_2, label: 'Inventario', color: VenezuelaColors.blue, onTap: () => onNavigate(1))),
                const SizedBox(width: 10),
                Expanded(child: QuickActionTile(icon: Icons.local_shipping, label: 'Entregas', color: VenezuelaColors.red, onTap: () => onNavigate(2))),
              ],
            ),
          ],
        );
      },
    );
  }
}
