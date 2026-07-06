import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

/// Lista de registros en cola local pendientes de sincronización.
class PendingQueuePanel extends StatelessWidget {
  const PendingQueuePanel({
    super.key,
    required this.sync,
    this.onSync,
    this.compact = false,
  });

  final SyncService sync;
  final Future<void> Function()? onSync;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: sync,
      builder: (context, _) {
        final pending = sync.listPending();
        if (pending.isEmpty) {
          if (compact) return const SizedBox.shrink();
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(Icons.check_circle_outline, color: VenezuelaColors.blue.withValues(alpha: 0.8)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'No hay registros pendientes de sincronización.',
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ),
                ],
              ),
            ),
          );
        }

        return Card(
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                color: VenezuelaColors.blueContainer,
                padding: const EdgeInsets.fromLTRB(16, 12, 8, 12),
                child: Row(
                  children: [
                    Icon(Icons.cloud_upload_outlined, color: VenezuelaColors.onBlueContainer, size: 22),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${pending.length} pendiente(s) de sync',
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: VenezuelaColors.onBlueContainer,
                            ),
                          ),
                          Text(
                            'Se enviarán automáticamente al detectar conexión',
                            style: TextStyle(fontSize: 12, color: VenezuelaColors.onBlueContainer.withValues(alpha: 0.85)),
                          ),
                        ],
                      ),
                    ),
                    if (onSync != null)
                      IconButton(
                        onPressed: onSync,
                        tooltip: 'Sincronizar ahora',
                        icon: Icon(Icons.sync, color: VenezuelaColors.onBlueContainer),
                      ),
                  ],
                ),
              ),
              ...pending.map((item) {
                final type = item['type'] as String? ?? '';
                final createdAt = item['created_at'] as String?;
                return ListTile(
                  dense: compact,
                  leading: CircleAvatar(
                    radius: compact ? 16 : 18,
                    backgroundColor: VenezuelaColors.yellowContainer,
                    foregroundColor: VenezuelaColors.onYellowContainer,
                    child: Icon(_iconForType(type), size: compact ? 16 : 18),
                  ),
                  title: Text(SyncService.pendingSummary(item)),
                  subtitle: Text(
                    [
                      '${SyncService.typeLabel(type)}${createdAt != null ? ' · ${_formatTime(createdAt)}' : ''}',
                      if (item['last_error'] is String && (item['last_error'] as String).isNotEmpty)
                        item['last_error'] as String,
                    ].join('\n'),
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: item['last_error'] != null ? VenezuelaColors.red : null,
                    ),
                  ),
                );
              }),
            ],
          ),
        );
      },
    );
  }

  IconData _iconForType(String type) {
    return switch (type) {
      'invitado.registro' => Icons.person_add_outlined,
      'requerimiento.create' => Icons.shopping_bag_outlined,
      'inventario.create' => Icons.inventory_2_outlined,
      'inventario.update_cantidad' => Icons.edit_outlined,
      'entrega.marcar' => Icons.local_shipping_outlined,
      _ => Icons.pending_outlined,
    };
  }

  String _formatTime(String iso) {
    try {
      final dt = DateTime.parse(iso).toLocal();
      return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return '';
    }
  }
}

/// Barra de estado offline / caché / cola pendiente.
class OfflineBanner extends StatefulWidget {
  const OfflineBanner({super.key, required this.catalog, required this.sync, this.onRefresh});

  final CatalogService catalog;
  final SyncService sync;
  final Future<void> Function()? onRefresh;

  @override
  State<OfflineBanner> createState() => _OfflineBannerState();
}

class _OfflineBannerState extends State<OfflineBanner> {
  bool _online = true;
  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;

  @override
  void initState() {
    super.initState();
    _checkOnline();
    widget.sync.addListener(_onSyncChanged);
    _connectivitySub = Connectivity().onConnectivityChanged.listen((results) {
      final online = !results.contains(ConnectivityResult.none);
      if (!mounted) return;
      setState(() => _online = online);
      if (online && widget.sync.pendingCount > 0) {
        widget.sync.scheduleAutoSync();
      }
    });
  }

  @override
  void dispose() {
    _connectivitySub?.cancel();
    widget.sync.removeListener(_onSyncChanged);
    super.dispose();
  }

  void _onSyncChanged() {
    if (mounted) setState(() {});
  }

  Future<void> _checkOnline() async {
    final online = await widget.catalog.isOnline;
    if (!mounted) return;
    setState(() => _online = online);
  }

  String? get _catalogUpdatedAt {
    final raw = widget.catalog.cachedCatalog?['generated_at'] as String?;
    if (raw == null) return null;
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.day}/${dt.month} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.sync,
      builder: (context, _) {
        final pending = widget.sync.pendingCount;

        if (!_online) {
          return _BannerShell(
            color: VenezuelaColors.redContainer,
            icon: Icons.cloud_off,
            iconColor: VenezuelaColors.onRedContainer,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Sin conexión — los registros se guardan en el teléfono.', style: TextStyle(fontSize: 12)),
                if (pending > 0) ...[
                  const SizedBox(height: 4),
                  Text(
                    '$pending en cola local',
                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: VenezuelaColors.onRedContainer),
                  ),
                ],
              ],
            ),
          );
        }

        if (pending > 0) {
          return _BannerShell(
            color: VenezuelaColors.blueContainer,
            icon: Icons.cloud_upload_outlined,
            iconColor: VenezuelaColors.onBlueContainer,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        '$pending registro(s) pendiente(s) de sincronización',
                        style: TextStyle(
                          fontSize: 12,
                          color: VenezuelaColors.onBlueContainer,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    if (widget.onRefresh != null)
                      TextButton.icon(
                        onPressed: widget.onRefresh,
                        icon: Icon(Icons.sync, size: 18, color: VenezuelaColors.onBlueContainer),
                        label: Text('Sync', style: TextStyle(color: VenezuelaColors.onBlueContainer)),
                      ),
                  ],
                ),
                if (widget.sync.lastSyncError != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    widget.sync.lastSyncError!,
                    style: TextStyle(fontSize: 11, color: Colors.red.shade700),
                  ),
                ],
              ],
            ),
          );
        }

        final updated = _catalogUpdatedAt;
        return _BannerShell(
          color: VenezuelaColors.yellowContainer,
          icon: Icons.offline_pin,
          iconColor: VenezuelaColors.onYellowContainer,
          child: Row(
            children: [
              Expanded(
                child: Text(
                  updated != null
                      ? 'Caché offline · ${widget.catalog.municipiosCount} mun. · actualizada $updated'
                      : 'Datos offline: ${widget.catalog.municipiosCount} municipios, '
                          '${widget.catalog.parroquiasCount} parroquias',
                  style: TextStyle(fontSize: 12, color: VenezuelaColors.onYellowContainer),
                ),
              ),
              if (widget.onRefresh != null)
                IconButton(
                  onPressed: widget.onRefresh,
                  tooltip: 'Actualizar caché',
                  icon: Icon(Icons.refresh, size: 20, color: VenezuelaColors.onYellowContainer),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _BannerShell extends StatelessWidget {
  const _BannerShell({
    required this.color,
    required this.icon,
    required this.iconColor,
    required this.child,
  });

  final Color color;
  final IconData icon;
  final Color iconColor;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: color,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: iconColor),
          const SizedBox(width: 8),
          Expanded(child: child),
        ],
      ),
    );
  }
}
