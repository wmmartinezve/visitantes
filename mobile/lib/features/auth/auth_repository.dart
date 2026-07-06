import 'package:visitantes_mobile/core/api/api_client.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';

class AuthRepository {
  AuthRepository({ApiClient? apiClient}) : _api = apiClient ?? ApiClient();

  final ApiClient _api;
  MobileUser? _user;

  MobileUser? get currentUser => _user;

  Future<MobileUser> login(String email, String password) async {
    final response = await _api.dio.post<Map<String, dynamic>>(
      '/login',
      data: {
        'email': email,
        'password': password,
        'device_name': 'flutter-mobile',
      },
    );

    final data = response.data!;
    final token = data['token'] as String;
    await _api.tokenStorage.save(token);
    _user = MobileUser.fromJson(data['user'] as Map<String, dynamic>);
    return _user!;
  }

  Future<MobileUser?> restoreSession() async {
    final token = await _api.tokenStorage.read();
    if (token == null || token.isEmpty) {
      return null;
    }

    try {
      final response = await _api.dio.get<Map<String, dynamic>>('/me');
      _user = MobileUser.fromJson(response.data!);
      return _user;
    } catch (_) {
      await _api.tokenStorage.clear();
      return null;
    }
  }

  Future<void> logout() async {
    try {
      await _api.dio.post<void>('/logout');
    } catch (_) {}
    await _api.tokenStorage.clear();
    _user = null;
  }
}
