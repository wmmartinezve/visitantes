import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/theme/venezuela_colors.dart';

class InvitadoFotoPreview extends StatelessWidget {
  const InvitadoFotoPreview({
    super.key,
    this.fotoUrl,
    required this.nombreCompleto,
    this.height = 220,
    this.onAddFoto,
    this.uploading = false,
  });

  final String? fotoUrl;
  final String nombreCompleto;
  final double height;
  final VoidCallback? onAddFoto;
  final bool uploading;

  @override
  Widget build(BuildContext context) {
    final initial = nombreCompleto.isNotEmpty ? nombreCompleto[0].toUpperCase() : '?';
    final hasFoto = fotoUrl != null && fotoUrl!.isNotEmpty;

    return Card(
      clipBehavior: Clip.antiAlias,
      margin: EdgeInsets.zero,
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: hasFoto
            ? FutureBuilder<Map<String, String>>(
                future: _authHeaders(),
                builder: (context, snapshot) {
                  if (!snapshot.hasData) {
                    return const Center(child: CircularProgressIndicator());
                  }

                  final headers = snapshot.data!;

                  return Material(
                    color: VenezuelaColors.blueContainer,
                    child: InkWell(
                      onTap: () => _openFullScreen(context, fotoUrl!, headers),
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          Image.network(
                            fotoUrl!,
                            fit: BoxFit.cover,
                            headers: headers,
                            loadingBuilder: (context, child, progress) {
                              if (progress == null) return child;
                              return Center(
                                child: CircularProgressIndicator(
                                  value: progress.expectedTotalBytes != null
                                      ? progress.cumulativeBytesLoaded / progress.expectedTotalBytes!
                                      : null,
                                ),
                              );
                            },
                            errorBuilder: (_, __, ___) => _Placeholder(initial: initial, label: 'No se pudo cargar la foto'),
                          ),
                          Positioned(
                            left: 0,
                            right: 0,
                            bottom: 0,
                            child: Container(
                              padding: const EdgeInsets.fromLTRB(12, 24, 12, 10),
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.bottomCenter,
                                  end: Alignment.topCenter,
                                  colors: [Colors.black.withValues(alpha: 0.7), Colors.transparent],
                                ),
                              ),
                              child: Row(
                                children: [
                                  const Expanded(
                                    child: Text(
                                      'Foto testigo de ingreso',
                                      style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500),
                                    ),
                                  ),
                                  Icon(Icons.zoom_out_map, color: Colors.white.withValues(alpha: 0.9), size: 20),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              )
            : _Placeholder(
                initial: initial,
                label: uploading ? 'Subiendo foto…' : 'Sin foto de ingreso',
                onAddFoto: uploading ? null : onAddFoto,
                uploading: uploading,
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

  void _openFullScreen(BuildContext context, String url, Map<String, String> headers) {
    Navigator.of(context).push<void>(
      MaterialPageRoute<void>(
        fullscreenDialog: true,
        builder: (context) => InvitadoFotoFullScreen(
          fotoUrl: url,
          headers: headers,
          title: nombreCompleto,
        ),
      ),
    );
  }
}

class InvitadoFotoFullScreen extends StatelessWidget {
  const InvitadoFotoFullScreen({
    super.key,
    required this.fotoUrl,
    required this.headers,
    required this.title,
  });

  final String fotoUrl;
  final Map<String, String> headers;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(title, style: const TextStyle(fontSize: 16)),
      ),
      body: InteractiveViewer(
        minScale: 0.5,
        maxScale: 5,
        child: Center(
          child: Image.network(
            fotoUrl,
            headers: headers,
            fit: BoxFit.contain,
            loadingBuilder: (context, child, progress) {
              if (progress == null) return child;
              return const Center(child: CircularProgressIndicator(color: Colors.white));
            },
            errorBuilder: (_, __, ___) => const Padding(
              padding: EdgeInsets.all(24),
              child: Text(
                'No se pudo cargar la imagen.',
                style: TextStyle(color: Colors.white70),
                textAlign: TextAlign.center,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Placeholder extends StatelessWidget {
  const _Placeholder({
    required this.initial,
    required this.label,
    this.onAddFoto,
    this.uploading = false,
  });

  final String initial;
  final String label;
  final VoidCallback? onAddFoto;
  final bool uploading;

  @override
  Widget build(BuildContext context) {
    final content = Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (uploading)
          const Padding(
            padding: EdgeInsets.only(bottom: 12),
            child: CircularProgressIndicator(),
          )
        else
          Text(initial, style: const TextStyle(fontSize: 48, fontWeight: FontWeight.w700, color: VenezuelaColors.blue)),
        const SizedBox(height: 8),
        Text(label, style: TextStyle(fontSize: 13, color: VenezuelaColors.blue.withValues(alpha: 0.85))),
        if (onAddFoto != null) ...[
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: onAddFoto,
            icon: const Icon(Icons.photo_camera_outlined, size: 20),
            label: const Text('Agregar foto testigo'),
            style: FilledButton.styleFrom(backgroundColor: VenezuelaColors.red),
          ),
        ],
      ],
    );

    return Container(
      color: VenezuelaColors.blueContainer,
      alignment: Alignment.center,
      padding: const EdgeInsets.all(16),
      child: content,
    );
  }
}
