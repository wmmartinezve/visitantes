import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/media/photo_compression.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/invitado_avatar.dart';
import 'package:visitantes_mobile/shared/widgets/invitado_foto_preview.dart';
import 'package:visitantes_mobile/shared/widgets/invitado_menciones_section.dart';

class GuestDetailScreen extends StatefulWidget {
  const GuestDetailScreen({
    super.key,
    required this.invitadoId,
    required this.fieldApi,
    this.catalog,
    this.sync,
  });

  final int invitadoId;
  final FieldApi fieldApi;
  final CatalogService? catalog;
  final SyncService? sync;

  @override
  State<GuestDetailScreen> createState() => _GuestDetailScreenState();
}

class _GuestDetailScreenState extends State<GuestDetailScreen> {
  InvitadoModel? _invitado;
  bool _loading = true;
  bool _uploadingFoto = false;
  bool _savingMenciones = false;
  bool _isOnline = true;

  CatalogService get _catalog => widget.catalog ?? CatalogService();

  @override
  void initState() {
    super.initState();
    _load();
    _checkOnline();
  }

  Future<void> _checkOnline() async {
    final online = await _catalog.isOnline;
    if (!mounted) return;
    setState(() => _isOnline = online);
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    await _checkOnline();
    final inv = await widget.fieldApi.fetchInvitado(widget.invitadoId);
    if (!mounted) return;
    setState(() {
      _invitado = inv;
      _loading = false;
    });
  }

  Future<void> _saveMenciones({
    required List<String> ayudas,
    required List<String> salud,
    required List<String> tramites,
    String? nota,
  }) async {
    setState(() => _savingMenciones = true);

    try {
      final updated = await widget.fieldApi.updateInvitadoMenciones(
        invitadoId: widget.invitadoId,
        ayudas: ayudas,
        salud: salud,
        tramites: tramites,
        nota: nota,
        sync: widget.sync,
      );

      if (!mounted) return;
      setState(() => _invitado = updated);

      final online = await _catalog.isOnline;
      if (!mounted) return;
      setState(() => _isOnline = online);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            online ? 'Menciones guardadas.' : 'Menciones guardadas offline. Se sincronizarán al reconectar.',
          ),
        ),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final data = e.response?.data;
      var message = 'No se pudieron guardar las menciones.';
      if (data is Map && data['message'] is String) {
        message = data['message'] as String;
      }
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('StateError: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _savingMenciones = false);
    }
  }

  Future<void> _openFotoFullScreen(BuildContext context, String fotoUrl, String nombre) async {
    final token = await ApiClient().tokenStorage.read();
    final headers = token != null && token.isNotEmpty ? {'Authorization': 'Bearer $token'} : <String, String>{};

    if (!context.mounted) return;

    Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        fullscreenDialog: true,
        builder: (context) => InvitadoFotoFullScreen(
          fotoUrl: fotoUrl,
          headers: headers,
          title: nombre,
        ),
      ),
    );
  }

  Future<void> _addFoto() async {
    final inv = _invitado;
    if (inv == null || !inv.esJefeFamilia) return;

    if (!await _catalog.isOnline) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Se requiere conexión para subir la foto testigo.')),
      );
      return;
    }

    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 100);
    if (file == null || !mounted) return;

    setState(() => _uploadingFoto = true);

    try {
      final raw = await file.readAsBytes();
      final compressed = await PhotoCompression.compressWitnessPhoto(raw);

      final updated = await widget.fieldApi.uploadInvitadoFoto(widget.invitadoId, {
        'foto_base64': 'data:image/jpeg;base64,${base64Encode(compressed.bytes)}',
        'foto_mime': 'image/jpeg',
      });

      if (!mounted) return;
      setState(() => _invitado = updated);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Foto testigo guardada.')),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final data = e.response?.data;
      var message = 'No se pudo subir la foto.';
      if (data is Map && data['message'] is String) {
        message = data['message'] as String;
      }
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) setState(() => _uploadingFoto = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final inv = _invitado;

    return BrandedDetailScaffold(
      title: inv?.nombreCompleto ?? 'Invitado',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (inv != null) ...[
                    InvitadoFotoPreview(
                      fotoUrl: inv.fotoUrl,
                      nombreCompleto: inv.nombreCompleto,
                      uploading: _uploadingFoto,
                      onAddFoto: inv.esJefeFamilia &&
                              (inv.fotoUrl == null || inv.fotoUrl!.isEmpty) &&
                              !_uploadingFoto
                          ? _addFoto
                          : null,
                    ),
                    const SizedBox(height: 16),
                    Card(
                      clipBehavior: Clip.antiAlias,
                      child: IntrinsicHeight(
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const TricolorAccent(),
                            Expanded(
                              child: Padding(
                                padding: const EdgeInsets.all(16),
                                child: Row(
                                  children: [
                                    InvitadoAvatar(
                                      nombreCompleto: inv.nombreCompleto,
                                      fotoUrl: inv.fotoUrl,
                                      radius: 40,
                                      onTap: inv.fotoUrl != null && inv.fotoUrl!.isNotEmpty
                                          ? () => _openFotoFullScreen(context, inv.fotoUrl!, inv.nombreCompleto)
                                          : null,
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(inv.nombreCompleto, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                                          const SizedBox(height: 4),
                                          if (inv.edadLabel != null) _DetailLine(icon: Icons.cake_outlined, text: inv.edadLabel!),
                                          if (inv.registradoEl != null) _DetailLine(icon: Icons.event_outlined, text: 'Registro: ${inv.registradoEl}'),
                                          _DetailLine(icon: Icons.badge_outlined, text: inv.cedula ?? 'Sin cédula'),
                                          _DetailLine(icon: Icons.phone_outlined, text: inv.telefono ?? 'Sin teléfono'),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    InvitadoMencionesSection(
                      catalog: _catalog.mencionesCatalogo,
                      invitado: inv,
                      saving: _savingMenciones,
                      isOnline: _isOnline,
                      onSave: _saveMenciones,
                    ),
                    if (inv.miembrosFamilia.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      SectionHeader(title: 'Núcleo familiar (${inv.miembrosFamilia.length})'),
                      ...inv.miembrosFamilia.map(
                        (m) => Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: VenezuelaColors.yellowContainer,
                              foregroundColor: VenezuelaColors.onYellowContainer,
                              child: Text(m.nombreCompleto.isNotEmpty ? m.nombreCompleto[0].toUpperCase() : '?'),
                            ),
                            title: Text(m.nombreCompleto),
                            subtitle: Text(
                              [
                                if (m.parentesco != null && m.parentesco!.isNotEmpty) m.parentesco,
                                if (m.edad != null) '${m.edad} años',
                                m.cedula ?? 'Sin cédula',
                              ]
                                  .whereType<String>()
                                  .join(' · '),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ],
              ),
            ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  const _DetailLine({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 2),
      child: Row(
        children: [
          Icon(icon, size: 14, color: Theme.of(context).colorScheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Expanded(child: Text(text, style: Theme.of(context).textTheme.bodySmall)),
        ],
      ),
    );
  }
}
