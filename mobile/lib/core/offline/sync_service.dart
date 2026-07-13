import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:uuid/uuid.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/storage/local_db.dart';

class SyncService extends ChangeNotifier {
  SyncService({ApiClient? apiClient, CatalogService? catalogService, Connectivity? connectivity, FieldApi? fieldApi})
      : _api = apiClient ?? ApiClient(),
        _catalog = catalogService ?? CatalogService(apiClient: apiClient),
        _connectivity = connectivity ?? Connectivity(),
        _fieldApi = fieldApi;

  final ApiClient _api;
  final CatalogService _catalog;
  final Connectivity _connectivity;
  final FieldApi? _fieldApi;
  final _uuid = const Uuid();

  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  Timer? _debounceTimer;
  bool _syncInProgress = false;
  String? _lastSyncError;

  int get pendingCount => LocalDb.queue.length;

  String? get lastSyncError => _lastSyncError;

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
      'invitado.menciones' => 'Menciones de invitado',
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
      'invitado.menciones' => 'Menciones · Invitado #${map['invitado_id'] ?? '—'}',
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

  Future<String> enqueue(
    String type,
    Map<String, dynamic> payload, {
    bool syncImmediately = false,
  }) async {
    final clientId = _uuid.v4();
    await LocalDb.queue.put(clientId, {
      'client_id': clientId,
      'type': type,
      'payload': payload,
      'created_at': DateTime.now().toUtc().toIso8601String(),
    });
    notifyListeners();

    if (syncImmediately) {
      await syncPending();
    } else {
      scheduleAutoSync();
    }

    return clientId;
  }

  /// Escucha reconexión y sincroniza la cola automáticamente.
  void startAutoSync() {
    _connectivitySub?.cancel();
    _connectivitySub = _connectivity.onConnectivityChanged.listen((results) {
      if (results.contains(ConnectivityResult.none)) return;
      scheduleAutoSync();
    });
    scheduleAutoSync();
  }

  void stopAutoSync() {
    _connectivitySub?.cancel();
    _connectivitySub = null;
    _debounceTimer?.cancel();
    _debounceTimer = null;
  }

  void scheduleAutoSync() {
    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 900), () {
      unawaited(_runAutoSync());
    });
  }

  Future<void> _runAutoSync() async {
    if (_syncInProgress || pendingCount == 0) return;
    if (!await _catalog.isOnline) return;

    _syncInProgress = true;
    try {
      await syncPending();
    } finally {
      _syncInProgress = false;
    }
  }

  /// Reemplaza menciones pendientes del mismo Invitado antes de encolar uno nuevo.
  Future<String> enqueueInvitadoMencionesUpdate({
    required int invitadoId,
    required List<String> ayudas,
    required List<String> salud,
    required List<String> tramites,
    String? nota,
  }) async {
    for (final key in LocalDb.queue.keys.toList()) {
      final raw = LocalDb.queue.get(key);
      if (raw is! Map) continue;
      final item = Map<String, dynamic>.from(raw);
      if (item['type'] != 'invitado.menciones') continue;
      final payload = item['payload'];
      if (payload is Map && payload['invitado_id'] == invitadoId) {
        await LocalDb.queue.delete(key);
      }
    }

    return enqueue('invitado.menciones', {
      'invitado_id': invitadoId,
      'menciones_ayudas': ayudas,
      'menciones_salud': salud,
      'menciones_tramites': tramites,
      if (nota != null && nota.trim().isNotEmpty) 'menciones_nota': nota.trim(),
    });
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

  Future<void> _storeQueueError(String? clientId, String error) async {
    if (clientId == null || clientId.isEmpty) return;
    final raw = LocalDb.queue.get(clientId);
    if (raw is! Map) return;
    await LocalDb.queue.put(clientId, {
      ...Map<String, dynamic>.from(raw),
      'last_error': error,
      'last_error_at': DateTime.now().toUtc().toIso8601String(),
    });
  }

  /// Elimina un ítem de la cola local (p. ej. registro fallido que se rehará online).
  Future<void> discardFromQueue(String clientId) async {
    await LocalDb.queue.delete(clientId);
    notifyListeners();
  }

  Future<SyncResult> syncPending() async {
    if (!await _catalog.isOnline || pendingCount == 0) {
      return SyncResult(ok: 0, failed: 0);
    }

    final pending = listPending();
    var ok = 0;
    var failed = 0;
    String? lastError;

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
          options: Options(
            sendTimeout: const Duration(seconds: 120),
            receiveTimeout: const Duration(seconds: 60),
          ),
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
            final error = result['error'] as String? ?? 'No se pudo sincronizar.';
            lastError = error;
            await _storeQueueError(result['client_id'] as String?, error);
          }
        }
      } on DioException catch (e) {
        failed += batch.length;
        lastError = _dioErrorMessage(e);
        break;
      } catch (e) {
        failed += batch.length;
        lastError = e.toString();
        break;
      }
    }

    if (ok > 0) {
      await _catalog.ensureCached(force: true);
    }

    notifyListeners();
    _lastSyncError = lastError;
    return SyncResult(ok: ok, failed: failed, lastError: lastError);
  }

  String _dioErrorMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      if (data['message'] is String) return data['message'] as String;
      final errors = data['errors'];
      if (errors is Map) {
        for (final entry in errors.entries) {
          final value = entry.value;
          if (value is List && value.isNotEmpty) return value.first.toString();
        }
      }
    }

    return switch (e.type) {
      DioExceptionType.connectionError ||
      DioExceptionType.connectionTimeout ||
      DioExceptionType.sendTimeout ||
      DioExceptionType.receiveTimeout =>
        'No se pudo conectar con el servidor. Verifique su internet.',
      _ => 'Error al sincronizar con el servidor.',
    };
  }

  /// Sincroniza la cola local y actualiza el catálogo offline.
  Future<RefreshAllResult> refreshAll({bool syncQueue = true}) async {
    final online = await _catalog.isOnline;
    if (!online) {
      return RefreshAllResult(online: false, sync: SyncResult(ok: 0, failed: 0), catalogRefreshed: false);
    }

    final syncResult = syncQueue ? await syncPending() : SyncResult(ok: 0, failed: 0);
    final catalog = await _catalog.ensureCached(force: true);
    var fieldDataRefreshed = false;
    if (_fieldApi != null) {
      fieldDataRefreshed = await _fieldApi.refreshAnfitrionCaches();
    }
    notifyListeners();

    return RefreshAllResult(
      online: true,
      sync: syncResult,
      catalogRefreshed: catalog != null,
      fieldDataRefreshed: fieldDataRefreshed,
      hadSyncActivity: syncResult.ok > 0,
    );
  }
}

class SyncResult {
  SyncResult({required this.ok, required this.failed, this.lastError});

  final int ok;
  final int failed;
  final String? lastError;

  bool get hasErrors => failed > 0 || (lastError != null && ok == 0);
}

class RefreshAllResult {
  RefreshAllResult({
    required this.online,
    required this.sync,
    required this.catalogRefreshed,
    this.fieldDataRefreshed = false,
    this.hadSyncActivity = false,
  });

  final bool online;
  final SyncResult sync;
  final bool catalogRefreshed;
  final bool fieldDataRefreshed;
  final bool hadSyncActivity;
}
