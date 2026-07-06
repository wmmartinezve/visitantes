import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class InvitadoFotoPreview extends StatelessWidget {
  const InvitadoFotoPreview({
    super.key,
    required this.fotoUrl,
    required this.nombreCompleto,
    this.height = 220,
  });

  final String fotoUrl;
  final String nombreCompleto;
  final double height;

  @override
  Widget build(BuildContext context) {
    final initial = nombreCompleto.isNotEmpty ? nombreCompleto[0].toUpperCase() : '?';

    return Card(
      clipBehavior: Clip.antiAlias,
      margin: EdgeInsets.zero,
      child: FutureBuilder<Map<String, String>>(
        future: _authHeaders(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return SizedBox(
              height: height,
              child: const Center(child: CircularProgressIndicator()),
            );
          }

          return Stack(
            fit: StackFit.expand,
            children: [
              Image.network(
                fotoUrl,
                height: height,
                width: double.infinity,
                fit: BoxFit.cover,
                headers: snapshot.data,
                errorBuilder: (_, __, ___) => Container(
                  height: height,
                  color: VenezuelaColors.blueContainer,
                  alignment: Alignment.center,
                  child: Text(initial, style: const TextStyle(fontSize: 48, fontWeight: FontWeight.w700, color: VenezuelaColors.blue)),
                ),
              ),
              Positioned(
                left: 0,
                right: 0,
                bottom: 0,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.bottomCenter,
                      end: Alignment.topCenter,
                      colors: [Colors.black.withValues(alpha: 0.65), Colors.transparent],
                    ),
                  ),
                  child: const Text(
                    'Foto testigo de ingreso',
                    style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500),
                  ),
                ),
              ),
            ],
          );
        },
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
