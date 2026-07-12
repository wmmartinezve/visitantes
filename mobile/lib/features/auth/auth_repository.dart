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
      _user = _parseUserResponse(response.data);
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

  Future<MobileUser> fetchCurrentUser() async {
    final response = await _api.dio.get<Map<String, dynamic>>('/me');
    _user = _parseUserResponse(response.data);
    return _user!;
  }

  Future<MobileUser> updateProfile({required String name, required String email}) async {
    final response = await _api.dio.put<Map<String, dynamic>>(
      '/profile',
      data: {'name': name, 'email': email},
    );
    _user = _parseUserResponse(response.data);
    return _user!;
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.dio.put<void>(
      '/profile/password',
      data: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }

  Future<void> sendPasswordResetEmail(String email) async {
    await _api.dio.post<void>(
      '/forgot-password',
      data: {'email': email},
    );
  }

  Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.dio.post<void>(
      '/reset-password',
      data: {
        'token': token,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }

  MobileUser _parseUserResponse(Map<String, dynamic>? payload) {
    if (payload == null) {
      throw StateError('Respuesta inválida del servidor');
    }

    final userJson = payload['data'] is Map<String, dynamic>
        ? payload['data'] as Map<String, dynamic>
        : payload;

    return MobileUser.fromJson(userJson);
  }

  Future<MobileUser> setActiveHogar(int hogarSolidarioId) async {
    final response = await _api.dio.put<Map<String, dynamic>>(
      '/hogar-activo',
      data: {'hogar_solidario_id': hogarSolidarioId},
    );
    _user = _parseUserResponse(response.data);
    return _user!;
  }
}
