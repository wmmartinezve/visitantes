import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/field_models.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';
import 'package:visitantes_mobile/shared/widgets/brand_widgets.dart';
import 'package:visitantes_mobile/shared/widgets/requirement_card.dart';

class RequirementsListScreen extends StatefulWidget {
  const RequirementsListScreen({super.key, required this.fieldApi});

  final FieldApi fieldApi;

  @override
  State<RequirementsListScreen> createState() => _RequirementsListScreenState();
}

class _RequirementsListScreenState extends State<RequirementsListScreen> {
  String _filtro = 'todos';
  List<RequerimientoModel> _items = [];
  bool _loading = true;

  static const _filtros = [
    ('todos', 'Todos'),
    ('pendiente', 'Pendiente'),
    ('asignado', 'Asignado'),
    ('entregado', 'Entregado'),
  ];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final list = await widget.fieldApi.fetchRequerimientos(estatus: _filtro);
    if (!mounted) return;
    setState(() {
      _items = list;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: Row(
            children: [
              for (final (value, label) in _filtros)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(label),
                    selected: _filtro == value,
                    selectedColor: VenezuelaColors.blueContainer,
                    checkmarkColor: VenezuelaColors.blue,
                    labelStyle: TextStyle(
                      color: _filtro == value ? VenezuelaColors.blue : null,
                      fontWeight: _filtro == value ? FontWeight.w600 : FontWeight.normal,
                    ),
                    onSelected: (_) {
                      setState(() => _filtro = value);
                      _load();
                    },
                  ),
                ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty
                      ? ListView(
                          children: const [
                            EmptyState(
                              icon: Icons.inventory_2_outlined,
                              title: 'Sin requerimientos',
                              message: 'No hay requerimientos con el filtro seleccionado.',
                            ),
                          ],
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
                          itemCount: _items.length,
                          itemBuilder: (context, i) => RequirementCard(requerimiento: _items[i]),
                        ),
                ),
        ),
      ],
    );
  }
}
