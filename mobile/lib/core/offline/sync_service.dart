import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class SyncService extends ChangeNotifier {
  SyncService({ApiClient? apiClient, CatalogService? catalogService})
      : _api = apiClient ?? ApiClient(),
        _catalog = catalogService ?? CatalogService(apiClient: apiClient);

  final ApiClient _api;
  final CatalogService _catalog;
  final _uuid = const Uuid();

  int get pendingCount => LocalDb.queue.length;

  List<Map<String, dynamic>> listPending() {
    return LocalDb.queue.values
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList()
      ..sort((a, b) => (a['created_at'] as String? ?? '').compareTo(b['created_at'] as String? ?? ''));
  }

  static String typeLabel(String type) {
    return switch (type) {
      'invitado.registro' => 'Registro de invitado',
      'requerimiento.create' => 'Requerimiento',
      'inventario.create' => 'Inventario',
      'inventario.update_cantidad' => 'Ajuste de stock',
      'entrega.marcar' => 'Entrega',
      _ => type,
    };
  }

  static String pendingSummary(Map<String, dynamic> item) {
    final type = item['type'] as String? ?? '';
    final payload = item['payload'];
    final map = payload is Map ? Map<String, dynamic>.from(payload) : <String, dynamic>{};

    return switch (type) {
      'invitado.registro' => _joinParts([
          map['nombre'] as String?,
          map['apellido'] as String?,
        ], fallback: 'Nuevo invitado'),
      'requerimiento.create' => _insumoLabel(map, fallback: 'Requerimiento'),
      'inventario.create' => _insumoLabel(map, fallback: 'Ítem de inventario'),
      'inventario.update_cantidad' => 'Stock → ${map['cantidad'] ?? '—'} (ítem #${map['inventario_id'] ?? '—'})',
      'entrega.marcar' => 'Entrega #${map['requerimiento_id'] ?? '—'}',
      _ => typeLabel(type),
    };
  }

  static String _insumoLabel(Map<String, dynamic> map, {required String fallback}) {
    final sub = map['subcategoria'] as String?;
    final cat = map['categoria'] as String?;
    if (sub != null && sub.isNotEmpty) {
      return cat != null && cat.isNotEmpty ? '$cat · $sub' : sub;
    }
    final legacy = map['item_solicitado'] as String? ?? map['item_nombre'] as String?;
    if (legacy != null && legacy.isNotEmpty) return legacy;
    return fallback;
  }

  static String _joinParts(List<String?> parts, {required String fallback}) {
    final text = parts.whereType<String>().where((p) => p.isNotEmpty).join(' ');
    return text.isEmpty ? fallback : text;
  }

  Future<String> enqueue(String type, Map<String, dynamic> payload) async {
    final clientId = _uuid.v4();
    await LocalDb.queue.put(clientId, {
      'client_id': clientId,
      'type': type,
      'payload': payload,
      'created_at': DateTime.now().toUtc().toIso8601String(),
    });
    notifyListeners();
    return clientId;
  }

  /// Reemplaza ajustes pendientes del mismo ítem antes de encolar uno nuevo.
  Future<String> enqueueInventarioCantidadUpdate({
    required int inventarioId,
    required int cantidad,
  }) async {
    for (final key in LocalDb.queue.keys.toList()) {
      final raw = LocalDb.queue.get(key);
      if (raw is! Map) continue;
      final item = Map<String, dynamic>.from(raw);
      if (item['type'] != 'inventario.update_cantidad') continue;
      final payload = item['payload'];
      if (payload is Map && payload['inventario_id'] == inventarioId) {
        await LocalDb.queue.delete(key);
      }
    }

    return enqueue('inventario.update_cantidad', {
      'inventario_id': inventarioId,
      'cantidad': cantidad,
    });
  }

  Future<SyncResult> syncPending() async {
    if (!await _catalog.isOnline || pendingCount == 0) {
      return SyncResult(ok: 0, failed: 0);
    }

    final pending = listPending();
    var ok = 0;
    var failed = 0;

    const batchSize = 10;
    for (var i = 0; i < pending.length; i += batchSize) {
      final batch = pending.skip(i).take(batchSize).toList();

      try {
        final response = await _api.dio.post<Map<String, dynamic>>(
          '/sync',
          data: {
            'items': batch
                .map((item) => {
                      'client_id': item['client_id'],
                      'type': item['type'],
                      'payload': item['payload'],
                    })
                .toList(),
          },
        );

        final results = response.data?['results'] as List<dynamic>? ?? [];
        for (final raw in results) {
          if (raw is! Map) continue;
          final result = Map<String, dynamic>.from(raw);
          if (result['status'] == 'ok') {
            await LocalDb.queue.delete(result['client_id']);
            ok++;
          } else {
            failed++;
          }
        }
      } catch (_) {
        failed += batch.length;
        break;
      }
    }

    if (ok > 0) {
      await _catalog.refresh(force: true);
    }

    notifyListeners();
    return SyncResult(ok: ok, failed: failed);
  }

  /// Sincroniza la cola local y actualiza el catálogo offline.
  Future<RefreshAllResult> refreshAll({bool syncQueue = true}) async {
    final online = await _catalog.isOnline;
    if (!online) {
      return RefreshAllResult(online: false, sync: SyncResult(ok: 0, failed: 0), catalogRefreshed: false);
    }

    final syncResult = syncQueue ? await syncPending() : SyncResult(ok: 0, failed: 0);
    final catalog = await _catalog.refresh(force: true);
    notifyListeners();

    return RefreshAllResult(
      online: true,
      sync: syncResult,
      catalogRefreshed: catalog != null,
    );
  }
}

class SyncResult {
  SyncResult({required this.ok, required this.failed});
  final int ok;
  final int failed;
}

class RefreshAllResult {
  RefreshAllResult({
    required this.online,
    required this.sync,
    required this.catalogRefreshed,
  });

  final bool online;
  final SyncResult sync;
  final bool catalogRefreshed;
}
