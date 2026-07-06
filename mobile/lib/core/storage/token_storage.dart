import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  TokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;
  static const _key = 'auth_token';

  Future<void> save(String token) => _storage.write(key: _key, value: token);

  Future<String?> read() => _storage.read(key: _key);

  Future<void> clear() => _storage.delete(key: _key);
}
