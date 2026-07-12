import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/auth/auth_repository.dart';
import 'package:visitantes_mobile/features/auth/profile_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/guests_list_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/register_guest_screen.dart';
import 'package:visitantes_mobile/shared/widgets/app_scaffold.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/pending_queue_panel.dart';

class AnfitrionShell extends StatefulWidget {
  const AnfitrionShell({
    super.key,
    required this.user,
    required this.catalog,
    required this.sync,
    required this.auth,
    required this.onLogout,
    required this.onUserUpdated,
  });

  final MobileUser user;
  final CatalogService catalog;
  final SyncService sync;
  final AuthRepository auth;
  final VoidCallback onLogout;
  final ValueChanged<MobileUser> onUserUpdated;

  @override
  State<AnfitrionShell> createState() => _AnfitrionShellState();
}

class _AnfitrionShellState extends State<AnfitrionShell> {
  late int _index;
  int _refreshTick = 0;
  bool _showProfile = false;
  late MobileUser _user = widget.user;
  late final FieldApi _fieldApi = FieldApi(catalogService: widget.catalog);

  /// Sin hogar: el anfitrión debe crearlo en el wizard (nunca pre-asignado).
  bool get _sinHogar =>
      _user.debeRegistrarHogar || widget.catalog.requiereRegistroHogar;

  bool get _requiereRegistroHogar => _sinHogar;

  bool get _nucleoYaRegistrado =>
      !_sinHogar && (_user.tieneNucleoFamiliar || widget.catalog.tieneNucleoFamiliarEnHogar);

  String get _hogarEtiqueta {
    if (_sinHogar) return 'Sin registrar';
    final nombre = _user.refugioNombre ?? _nombreHogarEnCatalogo;
    if (nombre != null && nombre.isNotEmpty) return nombre;
    if (_user.refugioId != null) return 'Hogar #${_user.refugioId}';
    return '—';
  }

  String? get _nombreHogarEnCatalogo {
    final operador = widget.catalog.cachedCatalog?['operador'];
    if (operador is! Map) return null;
    final hogar = (operador['hogar_solidario'] ?? operador['refugio']) as Map?;
    if (hogar is! Map) return null;
    return (hogar['nombre'] ?? hogar['codigo']) as String?;
  }

  @override
  void initState() {
    super.initState();
    _index = _requiereRegistroHogar ? 1 : 0;
    widget.catalog.addListener(_onCatalogOrUserContextChanged);
  }

  @override
  void dispose() {
    widget.catalog.removeListener(_onCatalogOrUserContextChanged);
    super.dispose();
  }

  void _onCatalogOrUserContextChanged() {
    if (!mounted) return;
    if (_requiereRegistroHogar && _index != 1) {
      setState(() => _index = 1);
    }
  }

  @override
  void didUpdateWidget(covariant AnfitrionShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.user.id != widget.user.id ||
        oldWidget.user.requiereRegistroHogar != widget.user.requiereRegistroHogar ||
        oldWidget.user.hogarVinculadoEn != widget.user.hogarVinculadoEn ||
        oldWidget.user.refugioId != widget.user.refugioId) {
      _user = widget.user;
      if (_requiereRegistroHogar && _index != 1) {
        _index = 1;
      }
    }
  }

  void _openProfile() => setState(() => _showProfile = true);

  void _closeProfile() => setState(() => _showProfile = false);

  void _handleUserUpdated(MobileUser user) {
    setState(() => _user = user);
    widget.catalog.syncOperadorFromUser(user);
    widget.onUserUpdated(user);
  }

  void _goTo(int index) {
    if (index == 1 && _requiereRegistroHogar) {
      setState(() {
        _index = 1;
        _refreshTick++;
      });
      return;
    }
    setState(() => _index = index);
  }

  void _bumpRefresh() => setState(() => _refreshTick++);

  Future<void> _syncFromHome() async {
    await widget.sync.refreshAll();
    _bumpRefresh();
  }

  Future<void> _onRegistered() async {
    await widget.catalog.refresh(force: true);
    if (_requiereRegistroHogar) {
      try {
        final user = await widget.auth.fetchCurrentUser();
        _handleUserUpdated(user);
      } catch (_) {}
    }
    _bumpRefresh();
  }

  @override
  Widget build(BuildContext context) {
    if (_showProfile) {
      return AppScaffold(
        title: 'Mi perfil',
        subtitle: _user.name,
        catalog: widget.catalog,
        sync: widget.sync,
        onLogout: widget.onLogout,
        onBack: _closeProfile,
        body: ProfileScreen(
          user: _user,
          auth: widget.auth,
          onUserUpdated: _handleUserUpdated,
        ),
      );
    }

    return ListenableBuilder(
      listenable: widget.catalog,
      builder: (context, _) {
        final pages = [
          _HomeTab(
            sync: widget.sync,
            hogarEtiqueta: _hogarEtiqueta,
            sinHogar: _sinHogar,
            onNavigate: _goTo,
            onSync: _syncFromHome,
          ),
          RegisterGuestScreen(
            key: const ValueKey('register-wizard'),
            user: _user,
            catalog: widget.catalog,
            sync: widget.sync,
            fieldApi: _fieldApi,
            nucleoYaRegistrado: _nucleoYaRegistrado,
            requiereRegistroHogar: _requiereRegistroHogar,
            onRegistered: _onRegistered,
            onUserUpdated: _handleUserUpdated,
          ),
          GuestsListScreen(key: ValueKey('guests-$_refreshTick'), fieldApi: _fieldApi, sync: widget.sync),
        ];

        return AppScaffold(
          title: _user.name,
          subtitle: _sinHogar ? 'Registre su hogar solidario' : 'Hogar solidario: $_hogarEtiqueta',
          catalog: widget.catalog,
          sync: widget.sync,
          onLogout: widget.onLogout,
          onProfile: _openProfile,
          onRefreshComplete: _bumpRefresh,
          body: pages[_index],
          bottomNav: NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: _goTo,
            destinations: [
              const NavigationDestination(icon: Icon(Icons.home), label: 'Inicio'),
              NavigationDestination(
                icon: const Icon(Icons.person_add),
                label: _sinHogar ? 'Registrar núcleo' : 'Registrar',
              ),
              const NavigationDestination(icon: Icon(Icons.groups), label: 'Invitados'),
            ],
          ),
        );
      },
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab({
    required this.hogarEtiqueta,
    required this.sync,
    required this.sinHogar,
    required this.onNavigate,
    required this.onSync,
  });

  final String hogarEtiqueta;
  final SyncService sync;
  final bool sinHogar;
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
            if (sinHogar) ...[
              Card(
                color: VenezuelaColors.red.withValues(alpha: 0.08),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.home_work_outlined, color: VenezuelaColors.red, size: 28),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'Registre su hogar solidario y núcleo familiar',
                              style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Complete el wizard de 4 pasos: hogar → jefe de familia → familiares → foto.',
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: () => onNavigate(1),
                        icon: const Icon(Icons.edit_road),
                        label: const Text('Iniciar registro'),
                        style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 12),
            ],
            StatCard(
              icon: Icons.home_work,
              title: sinHogar ? 'Hogar solidario pendiente' : 'Hogar solidario asignado',
              value: hogarEtiqueta,
              subtitle: sinHogar ? 'Registre hogar y núcleo familiar en la pestaña Registrar núcleo' : null,
              accent: sinHogar ? VenezuelaColors.red : VenezuelaColors.blue,
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
                Expanded(
                  child: QuickActionTile(
                    icon: Icons.person_add,
                    label: sinHogar ? 'Registrar núcleo' : 'Registrar Invitado',
                    color: VenezuelaColors.red,
                    onTap: () => onNavigate(1),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(child: QuickActionTile(icon: Icons.groups, label: 'Ver Invitados', color: VenezuelaColors.blue, onTap: () => onNavigate(2))),
              ],
            ),
          ],
        );
      },
    );
  }
}
