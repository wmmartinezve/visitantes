import 'package:flutter/material.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/pending_queue_panel.dart';
import 'package:visitantes_mobile/shared/widgets/venezuela_tricolor_bar.dart';

class AppScaffold extends StatefulWidget {
  const AppScaffold({
    super.key,
    required this.title,
    required this.subtitle,
    required this.catalog,
    required this.sync,
    required this.body,
    required this.onLogout,
    this.onProfile,
    this.onBack,
    this.bottomNav,
    this.onRefreshComplete,
  });

  final String title;
  final String subtitle;
  final CatalogService catalog;
  final SyncService sync;
  final Widget body;
  final VoidCallback onLogout;
  final VoidCallback? onProfile;
  final VoidCallback? onBack;
  final Widget? bottomNav;
  final VoidCallback? onRefreshComplete;

  @override
  State<AppScaffold> createState() => _AppScaffoldState();
}

class _AppScaffoldState extends State<AppScaffold> {
  bool _refreshing = false;

  Future<void> _refreshAll() async {
    if (_refreshing) return;
    setState(() => _refreshing = true);

    try {
      final result = await widget.sync.refreshAll();
      if (!mounted) return;

      if (!result.online) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Sin conexión. Revise su red e intente de nuevo.')),
        );
        return;
      }

      final parts = <String>[];
      if (result.sync.ok > 0) parts.add('${result.sync.ok} sincronizado(s)');
      if (result.sync.failed > 0) parts.add('${result.sync.failed} con error');
      if (result.catalogRefreshed) parts.add('caché actualizada');

      if (parts.isEmpty && widget.sync.pendingCount == 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Datos actualizados.'),
            backgroundColor: VenezuelaColors.blue,
          ),
        );
      } else if (parts.isNotEmpty) {
        final detail = result.sync.failed > 0 && result.sync.lastError != null
            ? '${parts.join(' · ')}\n${result.sync.lastError}'
            : parts.join(' · ');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(detail),
            backgroundColor: result.sync.failed > 0 ? VenezuelaColors.red : VenezuelaColors.blue,
            duration: Duration(seconds: result.sync.failed > 0 ? 6 : 4),
          ),
        );
      }

      widget.onRefreshComplete?.call();
    } finally {
      if (mounted) setState(() => _refreshing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const VenezuelaTricolorBar(height: 5),
          Container(
            color: VenezuelaColors.blue,
            padding: const EdgeInsets.fromLTRB(16, 12, 4, 12),
            child: Row(
              children: [
                if (widget.onBack != null)
                  IconButton(
                    onPressed: widget.onBack,
                    tooltip: 'Volver',
                    icon: const Icon(Icons.arrow_back, color: Colors.white),
                  )
                else
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: VenezuelaColors.yellow,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.volunteer_activism, color: VenezuelaColors.blue, size: 28),
                  ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Visitantes · ${AppConfig.estado}',
                        style: const TextStyle(color: Colors.white70, fontSize: 11, letterSpacing: 0.5),
                      ),
                      Text(widget.title, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w600)),
                      Text(widget.subtitle, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                    ],
                  ),
                ),
                if (widget.onProfile != null && widget.onBack == null)
                  IconButton(
                    onPressed: widget.onProfile,
                    tooltip: 'Mi perfil',
                    icon: const Icon(Icons.manage_accounts_outlined, color: Colors.white),
                  ),
                ListenableBuilder(
                  listenable: widget.sync,
                  builder: (context, _) {
                    final pending = widget.sync.pendingCount;
                    return Stack(
                      clipBehavior: Clip.none,
                      children: [
                        IconButton(
                          onPressed: _refreshing ? null : _refreshAll,
                          tooltip: 'Sincronizar y actualizar caché',
                          icon: _refreshing
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : const Icon(Icons.sync, color: Colors.white),
                        ),
                        if (pending > 0)
                          Positioned(
                            right: 6,
                            top: 6,
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: const BoxDecoration(color: VenezuelaColors.red, shape: BoxShape.circle),
                              constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
                              child: Text(
                                pending > 9 ? '9+' : '$pending',
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ),
                      ],
                    );
                  },
                ),
                IconButton(
                  onPressed: widget.onLogout,
                  tooltip: 'Cerrar sesión',
                  icon: const Icon(Icons.logout, color: Colors.white),
                ),
              ],
            ),
          ),
          OfflineBanner(catalog: widget.catalog, sync: widget.sync, onRefresh: _refreshing ? null : _refreshAll),
          Expanded(child: widget.body),
        ],
      ),
      bottomNavigationBar: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ?widget.bottomNav,
          const VenezuelaTricolorBar(height: 3),
        ],
      ),
    );
  }
}
