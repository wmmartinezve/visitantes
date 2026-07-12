import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/features/anfitrion/guest_detail_screen.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/guest_card.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

class GuestsListScreen extends StatefulWidget {
  const GuestsListScreen({super.key, required this.fieldApi, required this.sync});

  final FieldApi fieldApi;
  final SyncService sync;

  @override
  State<GuestsListScreen> createState() => _GuestsListScreenState();
}

class _GuestsListScreenState extends State<GuestsListScreen> {
  final _search = TextEditingController();
  List<InvitadoModel> _invitados = [];
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
    final list = await widget.fieldApi.fetchInvitados(query: _search.text.trim());
    if (!mounted) return;
    setState(() {
      _invitados = list;
      _loading = false;
    });
  }

  bool get _isSearching => _search.text.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        M3SearchField(
          controller: _search,
          hint: 'Buscar por nombre o cédula',
          onSearch: _load,
        ),
        if (!_loading && _invitados.isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Text('${_invitados.length} invitado(s)', style: Theme.of(context).textTheme.bodySmall),
          ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _invitados.isEmpty
                      ? ListView(
                          children: [
                            EmptyState(
                              icon: Icons.groups_outlined,
                              title: _isSearching ? 'Sin resultados' : 'No hay Invitados registrados',
                              message: _isSearching
                                  ? 'No se encontró ningún Invitado para "${_search.text.trim()}".'
                                  : 'Use la pestaña Registrar para agregar el primer Invitado.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                          itemCount: _invitados.length,
                          itemBuilder: (context, index) {
                            final inv = _invitados[index];
                            return GuestCard(
                              invitado: inv,
                              onTap: () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute<void>(
                                    builder: (_) => GuestDetailScreen(
                                      invitadoId: inv.navigationId,
                                      fieldApi: widget.fieldApi,
                                    ),
                                  ),
                                );
                                if (!mounted) return;
                                WidgetsBinding.instance.addPostFrameCallback((_) {
                                  if (mounted) _load();
                                });
                              },
                            );
                          },
                        ),
                ),
        ),
      ],
    );
  }
}
