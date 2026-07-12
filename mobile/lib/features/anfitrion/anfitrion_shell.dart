import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/auth/auth_repository.dart';
import 'package:visitantes_mobile/features/auth/profile_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/guests_list_screen.dart';
import 'package:visitantes_mobile/features/anfitrion/hogares_list_screen.dart';
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
  static const _tabInicio = 0;
  static const _tabRegistrar = 2;

  late int _index;
  int _refreshTick = 0;
  int _registerWizardKey = 0;
  bool _showProfile = false;
  bool _registrarNuevoHogar = false;
  late MobileUser _user = widget.user;
  late final FieldApi _fieldApi = FieldApi(catalogService: widget.catalog);

  bool get _sinHogar =>
      _user.debeRegistrarHogar || widget.catalog.requiereRegistroHogar;

  bool get _requiereRegistroHogar => _sinHogar;

  bool get _nucleoYaRegistrado =>
      !_registrarNuevoHogar &&
      !_sinHogar &&
      (_user.tieneNucleoFamiliar || widget.catalog.tieneNucleoFamiliarEnHogar);

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
    _index = _requiereRegistroHogar ? _tabRegistrar : _tabInicio;
    widget.catalog.addListener(_onCatalogOrUserContextChanged);
  }

  @override
  void dispose() {
    widget.catalog.removeListener(_onCatalogOrUserContextChanged);
    super.dispose();
  }

  void _onCatalogOrUserContextChanged() {
    if (!mounted) return;
    if (_requiereRegistroHogar && _index != _tabRegistrar) {
      setState(() => _index = _tabRegistrar);
    }
  }

  @override
  void didUpdateWidget(covariant AnfitrionShell oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.user.id != widget.user.id ||
        oldWidget.user.requiereRegistroHogar != widget.user.requiereRegistroHogar ||
        oldWidget.user.refugioId != widget.user.refugioId) {
      _user = widget.user;
      if (_requiereRegistroHogar && _index != _tabRegistrar) {
        _index = _tabRegistrar;
      }
    }
  }

  void _openProfile() => setState(() => _showProfile = true);

  void _closeProfile() => setState(() => _showProfile = false);

  void _handleUserUpdated(MobileUser user, {bool keepRegistrarOtroHogar = false}) {
    setState(() {
      _user = user;
      if (!keepRegistrarOtroHogar) {
        _registrarNuevoHogar = false;
      }
    });
    widget.catalog.syncOperadorFromUser(user);
    widget.onUserUpdated(user);
  }

  void _iniciarRegistroOtroHogar() {
    setState(() {
      _registrarNuevoHogar = true;
      _registerWizardKey++;
      _index = _tabRegistrar;
    });
  }

  Future<void> _cambiarHogarActivo(int hogarId) async {
    try {
      final user = await widget.auth.setActiveHogar(hogarId);
      await widget.catalog.ensureCached(force: true);
      _handleUserUpdated(user);
      _bumpRefresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Hogar activo actualizado.')),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo cambiar el hogar activo.')),
      );
    }
  }

  void _goTo(int index) {
    if (index == _tabRegistrar && _requiereRegistroHogar) {
      setState(() {
        _registrarNuevoHogar = false;
        _index = _tabRegistrar;
        _refreshTick++;
      });
      return;
    }

    setState(() {
      if (index == _tabRegistrar && index != _index && !_registrarNuevoHogar) {
        _registerWizardKey++;
      }
      if (index != _tabRegistrar) {
        _registrarNuevoHogar = false;
      }
      _index = index;
    });

    if (index == _tabRegistrar) {
      _refreshUserForRegisterTab();
    }
  }

  Future<void> _refreshUserForRegisterTab() async {
    try {
      final user = await widget.auth.fetchCurrentUser();
      if (!mounted) return;
      _handleUserUpdated(user, keepRegistrarOtroHogar: _registrarNuevoHogar);
    } catch (_) {}
  }

  void _bumpRefresh() => setState(() => _refreshTick++);

  Future<void> _syncFromHome() async {
    await widget.sync.refreshAll();
    _bumpRefresh();
  }

  Future<void> _onRegistered() async {
    await widget.catalog.ensureCached(force: true);
    try {
      final user = await widget.auth.fetchCurrentUser();
      _handleUserUpdated(user);
    } catch (_) {}
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

    final pages = [
      _HomeTab(
        key: ValueKey('home-$_refreshTick'),
        fieldApi: _fieldApi,
        sync: widget.sync,
        sinHogar: _sinHogar,
        hogaresCountFallback: _user.hogaresCount,
        invitadosCountFallback: 0,
        onNavigate: _goTo,
        onSync: _syncFromHome,
      ),
      HogaresListScreen(
        key: ValueKey('hogares-$_refreshTick'),
        fieldApi: _fieldApi,
        sync: widget.sync,
        hogarActivoId: _user.refugioId,
        puedeRegistrarOtro: _user.puedeRegistrarOtroHogar || widget.catalog.puedeRegistrarOtroHogar,
        onCambiarHogar: _cambiarHogarActivo,
        onRegistrarOtroHogar: _iniciarRegistroOtroHogar,
      ),
      RegisterGuestScreen(
        key: ValueKey('register-wizard-$_registerWizardKey'),
        user: _user,
        catalog: widget.catalog,
        sync: widget.sync,
        fieldApi: _fieldApi,
        nucleoYaRegistrado: _nucleoYaRegistrado,
        requiereRegistroHogar: _requiereRegistroHogar,
        registrarNuevoHogar: _registrarNuevoHogar,
        onRegistered: _onRegistered,
        onUserUpdated: _handleUserUpdated,
        onRegistrarOtroHogar: _iniciarRegistroOtroHogar,
      ),
      GuestsListScreen(key: ValueKey('guests-$_refreshTick'), fieldApi: _fieldApi, sync: widget.sync),
    ];

    return AppScaffold(
      title: _user.name,
      subtitle: _registrarNuevoHogar
          ? 'Nuevo hogar solidario'
          : _sinHogar
              ? 'Registre su primer hogar solidario'
              : 'Hogar activo: $_hogarEtiqueta',
      catalog: widget.catalog,
      sync: widget.sync,
      onLogout: widget.onLogout,
      onProfile: _openProfile,
      onRefreshComplete: _bumpRefresh,
      body: IndexedStack(
        index: _index,
        children: pages,
      ),
      bottomNav: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _goTo,
        destinations: [
          const NavigationDestination(icon: Icon(Icons.home), label: 'Inicio'),
          const NavigationDestination(icon: Icon(Icons.home_work), label: 'Hogares'),
          NavigationDestination(
            icon: const Icon(Icons.person_add),
            label: _sinHogar || _registrarNuevoHogar ? 'Registrar núcleo' : 'Registrar',
          ),
          const NavigationDestination(icon: Icon(Icons.groups), label: 'Invitados'),
        ],
      ),
    );
  }
}

class _HomeTab extends StatefulWidget {
  const _HomeTab({
    super.key,
    required this.fieldApi,
    required this.sync,
    required this.sinHogar,
    required this.hogaresCountFallback,
    required this.invitadosCountFallback,
    required this.onNavigate,
    required this.onSync,
  });

  final FieldApi fieldApi;
  final SyncService sync;
  final bool sinHogar;
  final int hogaresCountFallback;
  final int invitadosCountFallback;
  final ValueChanged<int> onNavigate;
  final Future<void> Function() onSync;

  @override
  State<_HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<_HomeTab> {
  HogaresResumen? _resumen;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadResumen();
  }

  Future<void> _loadResumen() async {
    setState(() => _loading = true);
    final resumen = await widget.fieldApi.fetchHogaresResumen();
    if (!mounted) return;
    setState(() {
      _resumen = resumen;
      _loading = false;
    });
  }

  Future<void> _refresh() async {
    await widget.onSync();
    await _loadResumen();
  }

  int get _hogaresCount => _resumen?.hogaresCount ?? widget.hogaresCountFallback;

  int get _invitadosCount => _resumen?.invitadosCount ?? widget.invitadosCountFallback;

  static const _tabHogares = 1;
  static const _tabRegistrar = 2;
  static const _tabInvitados = 3;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.sync,
      builder: (context, _) {
        final pending = widget.sync.pendingCount;

        return RefreshIndicator(
          onRefresh: _refresh,
          color: VenezuelaColors.blue,
          child: ListView(
            padding: const EdgeInsets.all(16),
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              if (widget.sinHogar) ...[
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
                                'Registre su primer hogar solidario y núcleo familiar',
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
                          onPressed: () => widget.onNavigate(_tabRegistrar),
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
              Row(
                children: [
                  Expanded(
                    child: StatCard(
                      icon: Icons.home_work,
                      title: 'Hogares registrados',
                      value: _loading ? '…' : '$_hogaresCount',
                      accent: VenezuelaColors.blue,
                      onTap: () => widget.onNavigate(_tabHogares),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: StatCard(
                      icon: Icons.groups,
                      title: 'Invitados',
                      value: _loading ? '…' : '$_invitadosCount',
                      accent: VenezuelaColors.red,
                      onTap: () => widget.onNavigate(_tabInvitados),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              StatCard(
                icon: Icons.cloud_sync,
                title: 'Registros pendientes de sync',
                value: '$pending',
                subtitle: pending == 0 ? 'Todo sincronizado' : 'En cola local',
                accent: pending > 0 ? VenezuelaColors.red : VenezuelaColors.blue,
              ),
              const SizedBox(height: 12),
              SectionHeader(
                title: 'Cola de sincronización',
                action: IconButton(
                  onPressed: _loading ? null : _refresh,
                  tooltip: 'Actualizar',
                  icon: const Icon(Icons.sync, color: VenezuelaColors.blue),
                ),
              ),
              PendingQueuePanel(sync: widget.sync, onSync: _refresh),
            ],
          ),
        );
      },
    );
  }
}
