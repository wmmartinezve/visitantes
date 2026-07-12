import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/core/utils/geo_links.dart';
import 'package:visitantes_mobile/core/utils/map_launcher.dart';
import 'package:visitantes_mobile/features/anfitrion/guest_detail_screen.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/guest_card.dart';

class HogarDetailScreen extends StatefulWidget {
  const HogarDetailScreen({
    super.key,
    required this.hogarId,
    required this.fieldApi,
    this.preview,
  });

  final int hogarId;
  final FieldApi fieldApi;
  final HogarSolidarioInfo? preview;

  @override
  State<HogarDetailScreen> createState() => _HogarDetailScreenState();
}

class _HogarDetailScreenState extends State<HogarDetailScreen> {
  HogarSolidarioDetail? _hogar;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final hogar = await widget.fieldApi.fetchHogarDetail(widget.hogarId);
    if (!mounted) return;
    setState(() {
      _hogar = hogar;
      _loading = false;
    });
  }

  Future<void> _openInvitado(InvitadoModel invitado) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => GuestDetailScreen(
          invitadoId: invitado.navigationId,
          fieldApi: widget.fieldApi,
        ),
      ),
    );
    if (!mounted) return;
    await _load();
  }

  Future<void> _openMapa(HogarSolidarioDetail hogar) async {
    final lat = hogar.latitud;
    final lng = hogar.longitud;
    if (lat == null || lng == null) return;
    await MapLauncher.open(
      context,
      GeoLinks.mapsQueryUrl(lat, lng),
      errorMessage: 'No se pudo abrir el mapa.',
    );
  }

  @override
  Widget build(BuildContext context) {
    final hogar = _hogar;
    final preview = widget.preview;
    final title = hogar?.codigo ?? preview?.codigo ?? 'Hogar solidario';

    return BrandedDetailScaffold(
      title: title,
      body: _loading && hogar == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              color: VenezuelaColors.blue,
              child: ListView(
                padding: const EdgeInsets.all(16),
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  if (hogar == null)
                    EmptyState(
                      icon: Icons.cloud_off_outlined,
                      title: 'No se pudo cargar el detalle',
                      message: preview != null
                          ? 'Revise su conexión e intente de nuevo.\n${preview.codigo}'
                          : 'Revise su conexión e intente de nuevo.',
                    )
                  else ...[
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
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      hogar.codigo,
                                      style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      hogar.tieneNucleoFamiliar ? 'Con núcleo familiar' : 'Sin núcleo familiar',
                                      style: Theme.of(context).textTheme.bodyMedium,
                                    ),
                                    if (hogar.registradoEl != null)
                                      Padding(
                                        padding: const EdgeInsets.only(top: 4),
                                        child: Text(
                                          'Registrado: ${hogar.registradoEl}',
                                          style: Theme.of(context).textTheme.bodySmall,
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
                    const SizedBox(height: 12),
                    FormSectionCard(
                      title: 'Ubicación',
                      icon: Icons.place_outlined,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _InfoRow(label: 'Dirección', value: hogar.direccionExacta ?? 'Sin dirección'),
                          if (hogar.ubicacionLabel != null)
                            _InfoRow(label: 'Territorio', value: hogar.ubicacionLabel!),
                          if (hogar.tieneCoordenadas)
                            _InfoRow(
                              label: 'Coordenadas',
                              value: '${hogar.latitud!.toStringAsFixed(6)}, ${hogar.longitud!.toStringAsFixed(6)}',
                            ),
                          if (hogar.tieneCoordenadas) ...[
                            const SizedBox(height: 8),
                            OutlinedButton.icon(
                              onPressed: () => _openMapa(hogar),
                              icon: const Icon(Icons.map_outlined),
                              label: const Text('Ver en mapa'),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    FormSectionCard(
                      title: 'Datos del hogar',
                      icon: Icons.home_work_outlined,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _InfoRow(label: 'Tipo de vivienda', value: hogar.tipoViviendaLabel ?? '—'),
                          _InfoRow(label: 'Anfitrión en el hogar', value: hogar.tipoAnfitrionLabel ?? '—'),
                          if (hogar.parentescoAnfitrion != null && hogar.parentescoAnfitrion!.isNotEmpty)
                            _InfoRow(label: 'Parentesco', value: hogar.parentescoAnfitrion!),
                        ],
                      ),
                    ),
                    if (_hasResponsable(hogar)) ...[
                      const SizedBox(height: 12),
                      FormSectionCard(
                        title: 'Responsable del hogar',
                        icon: Icons.person_outline,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            if (hogar.responsableNombre != null && hogar.responsableNombre!.isNotEmpty)
                              _InfoRow(label: 'Nombre', value: hogar.responsableNombre!),
                            if (hogar.responsableCedula != null && hogar.responsableCedula!.isNotEmpty)
                              _InfoRow(label: 'Cédula', value: hogar.responsableCedula!),
                            if (hogar.responsableTelefono != null && hogar.responsableTelefono!.isNotEmpty)
                              _InfoRow(label: 'Teléfono', value: hogar.responsableTelefono!),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    SectionHeader(title: 'Invitados hospedados (${hogar.invitadosCount})'),
                    if (hogar.jefeFamiliar != null)
                      GuestCard(
                        invitado: hogar.jefeFamiliar!,
                        onTap: () => _openInvitado(hogar.jefeFamiliar!),
                      )
                    else if (hogar.invitados.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Text('Este hogar aún no tiene Invitados registrados.'),
                      )
                    else
                      ...hogar.invitados
                          .where((inv) => inv.esJefeFamilia)
                          .map(
                            (inv) => GuestCard(
                              invitado: inv,
                              onTap: () => _openInvitado(inv),
                            ),
                          ),
                  ],
                ],
              ),
            ),
    );
  }

  bool _hasResponsable(HogarSolidarioDetail hogar) {
    return (hogar.responsableNombre?.isNotEmpty ?? false) ||
        (hogar.responsableCedula?.isNotEmpty ?? false) ||
        (hogar.responsableTelefono?.isNotEmpty ?? false);
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: Theme.of(context).textTheme.labelMedium?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          const SizedBox(height: 2),
          Text(value, style: Theme.of(context).textTheme.bodyMedium),
        ],
      ),
    );
  }
}
