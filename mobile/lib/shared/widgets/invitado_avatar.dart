import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class InvitadoAvatar extends StatefulWidget {
  const InvitadoAvatar({
    super.key,
    required this.nombreCompleto,
    this.fotoUrl,
    this.radius = 24,
    this.backgroundColor = VenezuelaColors.blueContainer,
    this.foregroundColor = VenezuelaColors.blue,
    this.onTap,
  });

  final String nombreCompleto;
  final String? fotoUrl;
  final double radius;
  final Color backgroundColor;
  final Color foregroundColor;
  final VoidCallback? onTap;

  @override
  State<InvitadoAvatar> createState() => _InvitadoAvatarState();
}

class _InvitadoAvatarState extends State<InvitadoAvatar> {
  late final Future<Map<String, String>> _headersFuture = _authHeaders();

  Future<Map<String, String>> _authHeaders() async {
    final token = await ApiClient().tokenStorage.read();
    if (token == null || token.isEmpty) {
      return const {};
    }
    return {'Authorization': 'Bearer $token'};
  }

  @override
  Widget build(BuildContext context) {
    final initial = widget.nombreCompleto.isNotEmpty ? widget.nombreCompleto[0].toUpperCase() : '?';

    if (widget.fotoUrl == null || widget.fotoUrl!.isEmpty) {
      return CircleAvatar(
        radius: widget.radius,
        backgroundColor: widget.backgroundColor,
        foregroundColor: widget.foregroundColor,
        child: Text(initial, style: TextStyle(fontWeight: FontWeight.w700, fontSize: widget.radius * 0.75)),
      );
    }

    final avatar = CircleAvatar(
      radius: widget.radius,
      backgroundColor: widget.backgroundColor,
      child: ClipOval(
        child: FutureBuilder<Map<String, String>>(
          future: _headersFuture,
          builder: (context, snapshot) {
            if (!context.mounted) return const SizedBox.shrink();
            if (!snapshot.hasData) {
              return SizedBox(
                width: widget.radius * 2,
                height: widget.radius * 2,
                child: Center(
                  child: Text(
                    initial,
                    style: TextStyle(fontWeight: FontWeight.w700, color: widget.foregroundColor),
                  ),
                ),
              );
            }

            return Image.network(
              widget.fotoUrl!,
              width: widget.radius * 2,
              height: widget.radius * 2,
              fit: BoxFit.cover,
              headers: snapshot.data,
              errorBuilder: (_, __, ___) => Center(
                child: Text(initial, style: TextStyle(fontWeight: FontWeight.w700, color: widget.foregroundColor)),
              ),
            );
          },
        ),
      ),
    );

    if (widget.onTap == null) return avatar;

    return GestureDetector(onTap: widget.onTap, child: avatar);
  }
}
