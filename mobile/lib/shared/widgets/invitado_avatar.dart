import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class InvitadoAvatar extends StatelessWidget {
  const InvitadoAvatar({
    super.key,
    required this.nombreCompleto,
    this.fotoUrl,
    this.radius = 24,
    this.backgroundColor = VenezuelaColors.blueContainer,
    this.foregroundColor = VenezuelaColors.blue,
  });

  final String nombreCompleto;
  final String? fotoUrl;
  final double radius;
  final Color backgroundColor;
  final Color foregroundColor;

  @override
  Widget build(BuildContext context) {
    final initial = nombreCompleto.isNotEmpty ? nombreCompleto[0].toUpperCase() : '?';

    if (fotoUrl == null || fotoUrl!.isEmpty) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: backgroundColor,
        foregroundColor: foregroundColor,
        child: Text(initial, style: TextStyle(fontWeight: FontWeight.w700, fontSize: radius * 0.75)),
      );
    }

    return CircleAvatar(
      radius: radius,
      backgroundColor: backgroundColor,
      child: ClipOval(
        child: FutureBuilder<Map<String, String>>(
          future: _authHeaders(),
          builder: (context, snapshot) {
            if (!snapshot.hasData) {
              return SizedBox(
                width: radius * 2,
                height: radius * 2,
                child: Center(child: Text(initial, style: TextStyle(fontWeight: FontWeight.w700, color: foregroundColor))),
              );
            }

            return Image.network(
              fotoUrl!,
              width: radius * 2,
              height: radius * 2,
              fit: BoxFit.cover,
              headers: snapshot.data,
              errorBuilder: (_, __, ___) => Center(
                child: Text(initial, style: TextStyle(fontWeight: FontWeight.w700, color: foregroundColor)),
              ),
            );
          },
        ),
      ),
    );
  }

  Future<Map<String, String>> _authHeaders() async {
    final token = await ApiClient().tokenStorage.read();
    if (token == null || token.isEmpty) {
      return const {};
    }

    return {'Authorization': 'Bearer $token'};
  }
}
