import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/anfitrion/guests_list_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/register_guest_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/requirements_list_screen.dart';
import 'package:visitantes_mobile/shared/widgets/app_scaffold.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/pending_queue_panel.dart';

class AnfitrionShell extends StatefulWidget {
  const AnfitrionShell({
    super.key,
    required this.user,
    required this.catalog,
    required this.sync,
    required this.onLogout,
  });

  final MobileUser user;
  final CatalogService catalog;
  final SyncService sync;
  final VoidCallback onLogout;

  @override
  State<AnfitrionShell> createState() => _AnfitrionShellState();
}

class _AnfitrionShellState extends State<AnfitrionShell> {
  int _index = 0;
  int _refreshTick = 0;
  late final FieldApi _fieldApi = FieldApi(catalogService: widget.catalog);

  void _goTo(int index) => setState(() => _index = index);

  void _bumpRefresh() => setState(() => _refreshTick++);

  Future<void> _syncFromHome() async {
    await widget.sync.refreshAll();
    _bumpRefresh();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      _HomeTab(sync: widget.sync, user: widget.user, onNavigate: _goTo, onSync: _syncFromHome),
      RegisterGuestScreen(catalog: widget.catalog, sync: widget.sync),
      GuestsListScreen(key: ValueKey('guests-$_refreshTick'), fieldApi: _fieldApi),
      RequirementsListScreen(key: ValueKey('reqs-$_refreshTick'), fieldApi: _fieldApi),
    ];

    return AppScaffold(
      title: widget.user.name,
      subtitle: 'Refugio: ${widget.user.refugioNombre ?? '—'}',
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
          NavigationDestination(icon: Icon(Icons.person_add), label: 'Registrar'),
          NavigationDestination(icon: Icon(Icons.groups), label: 'Invitados'),
          NavigationDestination(icon: Icon(Icons.inventory_2), label: 'Req.'),
        ],
      ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab({
    required this.user,
    required this.sync,
    required this.onNavigate,
    required this.onSync,
  });

  final MobileUser user;
  final SyncService sync;
  final ValueChanged<int> onNavigate;
  final Future<void> Function() onSync;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: sync,
      builder: (context, _) {
        final pending = sync.pendingCount;

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            StatCard(
              icon: Icons.home_work,
              title: 'Refugio asignado',
              value: user.refugioNombre ?? '—',
              accent: VenezuelaColors.blue,
            ),
            const SizedBox(height: 10),
            StatCard(
              icon: Icons.cloud_sync,
              title: 'Registros pendientes de sync',
              value: '$pending',
              subtitle: pending == 0 ? 'Todo sincronizado' : 'En cola local',
              accent: pending > 0 ? VenezuelaColors.red : VenezuelaColors.blue,
            ),
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
                Expanded(child: QuickActionTile(icon: Icons.person_add, label: 'Registrar Invitado', color: VenezuelaColors.red, onTap: () => onNavigate(1))),
                const SizedBox(width: 10),
                Expanded(child: QuickActionTile(icon: Icons.groups, label: 'Ver Invitados', color: VenezuelaColors.blue, onTap: () => onNavigate(2))),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(child: QuickActionTile(icon: Icons.inventory_2, label: 'Requerimientos', color: VenezuelaColors.blue, onTap: () => onNavigate(3))),
              ],
            ),
          ],
        );
      },
    );
  }
}
