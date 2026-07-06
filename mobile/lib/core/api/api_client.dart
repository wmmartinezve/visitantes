import 'package:dio/dio.dart';
import 'package:visitantes_mobile/config/app_config.dart';
import 'package:visitantes_mobile/core/storage/token_storage.dart';

class ApiClient {
  ApiClient._({TokenStorage? tokenStorage})
      : _tokenStorage = tokenStorage ?? TokenStorage(),
        dio = Dio(
          BaseOptions(
            baseUrl: AppConfig.apiBaseUrl,
            connectTimeout: const Duration(seconds: 20),
            receiveTimeout: const Duration(seconds: 30),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  static final ApiClient instance = ApiClient._();

  factory ApiClient() => instance;

  final Dio dio;
  final TokenStorage _tokenStorage;

  TokenStorage get tokenStorage => _tokenStorage;

  void updateBaseUrl(String url) {
    dio.options.baseUrl = url;
  }
}
