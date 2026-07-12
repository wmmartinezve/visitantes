import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/features/anfitrion/hogar_detail_screen.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

class HogaresListScreen extends StatefulWidget {
  const HogaresListScreen({
    super.key,
    required this.fieldApi,
    required this.sync,
    required this.puedeRegistrarOtro,
    required this.onRegistrarOtroHogar,
  });

  final FieldApi fieldApi;
  final SyncService sync;
  final bool puedeRegistrarOtro;
  final VoidCallback onRegistrarOtroHogar;

  @override
  State<HogaresListScreen> createState() => _HogaresListScreenState();
}

class _HogaresListScreenState extends State<HogaresListScreen> {
  final _search = TextEditingController();
  List<HogarSolidarioInfo> _hogares = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    widget.sync.addListener(_load);
    _load();
  }

  @override
  void dispose() {
    widget.sync.removeListener(_load);
    _search.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final resumen = await widget.fieldApi.fetchHogaresResumen();
    if (!mounted) return;
    setState(() {
      _hogares = resumen.hogares;
      _loading = false;
    });
  }

  List<HogarSolidarioInfo> get _filtered {
    final query = _search.text.trim().toLowerCase();
    if (query.isEmpty) return _hogares;
    return _hogares.where((hogar) {
      final codigo = hogar.codigo.toLowerCase();
      final direccion = (hogar.direccionExacta ?? '').toLowerCase();
      return codigo.contains(query) || direccion.contains(query);
    }).toList();
  }

  bool get _isSearching => _search.text.trim().isNotEmpty;

  Future<void> _openDetalle(HogarSolidarioInfo hogar) async {
    await Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        builder: (_) => HogarDetailScreen(
          hogarId: hogar.id,
          fieldApi: widget.fieldApi,
          preview: hogar,
        ),
      ),
    );
    if (!mounted) return;
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final hogares = _filtered;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        M3SearchField(
          controller: _search,
          hint: 'Buscar por código o dirección',
          onSearch: () => setState(() {}),
        ),
        if (!_loading && hogares.isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Text(
              '${hogares.length} hogar${hogares.length == 1 ? '' : 'es'}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ),
        if (widget.puedeRegistrarOtro)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: FilledButton.icon(
              onPressed: widget.onRegistrarOtroHogar,
              icon: const Icon(Icons.add_home_work),
              label: const Text('Registrar otro hogar y núcleo'),
              style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
            ),
          ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  color: VenezuelaColors.blue,
                  child: hogares.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: [
                            EmptyState(
                              icon: Icons.home_work_outlined,
                              title: _isSearching ? 'Sin resultados' : 'No hay hogares registrados',
                              message: _isSearching
                                  ? 'No se encontró ningún hogar para "${_search.text.trim()}".'
                                  : 'Use la pestaña Registrar núcleo para agregar su primer hogar solidario.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                          itemCount: hogares.length,
                          itemBuilder: (context, index) {
                            final hogar = hogares[index];
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8),
                              child: ListTile(
                                leading: const Icon(Icons.home_work, color: VenezuelaColors.red),
                                title: Text(hogar.codigo, style: const TextStyle(fontWeight: FontWeight.w600)),
                                subtitle: Text(
                                  [
                                    if (hogar.tieneNucleoFamiliar) 'Con núcleo' else 'Sin núcleo',
                                    '${hogar.invitadosCount} Invitado${hogar.invitadosCount == 1 ? '' : 'es'}',
                                    if (hogar.direccionExacta != null && hogar.direccionExacta!.isNotEmpty)
                                      hogar.direccionExacta!,
                                  ].join(' · '),
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                trailing: const Icon(Icons.chevron_right),
                                onTap: () => _openDetalle(hogar),
                              ),
                            );
                          },
                        ),
                ),
        ),
      ],
    );
  }
}
