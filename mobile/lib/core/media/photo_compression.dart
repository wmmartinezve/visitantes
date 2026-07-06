import 'dart:typed_data';

import 'package:flutter_image_compress/flutter_image_compress.dart';

/// Compresión de fotos testigo alineada con la web (≈0.8 MB, máx. 1280 px).
class PhotoCompression {
  PhotoCompression._();

  static const int maxSide = 1280;
  static const int targetMaxBytes = 838860; // ~0.8 MB
  static const int initialQuality = 85;

  static Future<PhotoCompressionResult> compressWitnessPhoto(Uint8List input) async {
    final originalSize = input.length;
    var quality = initialQuality;
    Uint8List output = Uint8List.fromList(
      await FlutterImageCompress.compressWithList(
        input,
        minWidth: maxSide,
        minHeight: maxSide,
        quality: quality,
        format: CompressFormat.jpeg,
        keepExif: false,
      ),
    );

    while (output.length > targetMaxBytes && quality > 45) {
      quality -= 10;
      output = Uint8List.fromList(
        await FlutterImageCompress.compressWithList(
          input,
          minWidth: maxSide,
          minHeight: maxSide,
          quality: quality,
          format: CompressFormat.jpeg,
          keepExif: false,
        ),
      );
    }

    return PhotoCompressionResult(
      bytes: output,
      originalBytes: originalSize,
      compressedBytes: output.length,
      quality: quality,
    );
  }

  static String formatSize(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(0)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
}

class PhotoCompressionResult {
  PhotoCompressionResult({
    required this.bytes,
    required this.originalBytes,
    required this.compressedBytes,
    required this.quality,
  });

  final Uint8List bytes;
  final int originalBytes;
  final int compressedBytes;
  final int quality;

  String get sizeLabel => PhotoCompression.formatSize(compressedBytes);
}
