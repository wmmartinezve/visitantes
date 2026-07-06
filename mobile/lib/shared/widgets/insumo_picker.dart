import 'package:flutter/material.dart';
import 'package:visitantes_mobile/shared/widgets/m3_text_field.dart';

/// Selector jerárquico de insumos: categoría → subcategoría.
class InsumoPicker extends StatefulWidget {
  const InsumoPicker({
    super.key,
    required this.catalogo,
    required this.onChanged,
    this.categoria,
    this.subcategoria,
  });

  final Map<String, dynamic> catalogo;
  final void Function(String? categoria, String? subcategoria) onChanged;
  final String? categoria;
  final String? subcategoria;

  @override
  State<InsumoPicker> createState() => _InsumoPickerState();
}

class _InsumoPickerState extends State<InsumoPicker> {
  String? _categoria;
  String? _subcategoria;

  List<String> get _categorias => widget.catalogo.keys.toList()..sort();

  List<String> get _subcategorias {
    if (_categoria == null || _categoria!.isEmpty) return [];
    final raw = widget.catalogo[_categoria];
    if (raw is! List) return [];
    return raw.map((e) => e.toString()).toList();
  }

  @override
  void initState() {
    super.initState();
    _categoria = widget.categoria;
    _subcategoria = widget.subcategoria;
  }

  @override
  void didUpdateWidget(covariant InsumoPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.categoria != oldWidget.categoria) {
      _categoria = widget.categoria;
    }
    if (widget.subcategoria != oldWidget.subcategoria) {
      _subcategoria = widget.subcategoria;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        M3SelectField(
          label: 'Categoría',
          icon: M3FieldIcons.category,
          items: _categorias,
          value: _categoria,
          validator: (v) => v == null || v.isEmpty ? 'Seleccione una categoría' : null,
          onChanged: (value) {
            setState(() {
              _categoria = value;
              _subcategoria = null;
            });
            widget.onChanged(_categoria, null);
          },
        ),
        M3SelectField(
          label: 'Subcategoría',
          icon: M3FieldIcons.item,
          items: _subcategorias,
          value: _subcategoria,
          validator: (v) => v == null || v.isEmpty ? 'Seleccione una subcategoría' : null,
          onChanged: (value) {
            setState(() => _subcategoria = value);
            widget.onChanged(_categoria, _subcategoria);
          },
        ),
      ],
    );
  }
}

Map<String, dynamic> insumosCatalogoFromCache(Map<String, dynamic>? catalog) {
  final raw = catalog?['insumos_catalogo'];
  if (raw is! Map) return {};
  return Map<String, dynamic>.from(raw);
}
