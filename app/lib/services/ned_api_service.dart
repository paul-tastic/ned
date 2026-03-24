import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import '../models/dashboard_response.dart';
import '../models/server_detail.dart';

class NedApiService {
  static final NedApiService _instance = NedApiService._internal();
  factory NedApiService() => _instance;
  NedApiService._internal();

  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  String? baseUrl;
  String? token;

  bool get isAuthenticated => baseUrl != null && token != null;

  Future<void> loadCredentials() async {
    baseUrl = await _storage.read(key: 'ned_base_url');
    token = await _storage.read(key: 'ned_token');
  }

  Future<void> _saveCredentials() async {
    if (baseUrl != null) {
      await _storage.write(key: 'ned_base_url', value: baseUrl);
    }
    if (token != null) {
      await _storage.write(key: 'ned_token', value: token);
    }
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  String _buildUrl(String path) {
    final base = baseUrl!.endsWith('/') ? baseUrl!.substring(0, baseUrl!.length - 1) : baseUrl!;
    return '$base$path';
  }

  Future<bool> login(String url, String email, String password) async {
    baseUrl = url;
    try {
      final response = await http.post(
        Uri.parse(_buildUrl('/api/auth/login')),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        token = data['token'] as String?;
        if (token != null) {
          await _saveCredentials();
          return true;
        }
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<void> connectWithQr(String qrPayload) async {
    final data = jsonDecode(qrPayload) as Map<String, dynamic>;
    baseUrl = data['url'] as String?;
    token = data['token'] as String?;
    await _saveCredentials();
  }

  Future<DashboardResponse> getDashboard() async {
    final response = await http.get(
      Uri.parse(_buildUrl('/api/dashboard')),
      headers: _headers,
    );

    if (response.statusCode == 401) {
      throw UnauthenticatedException();
    }

    if (response.statusCode != 200) {
      throw ApiException('Failed to load dashboard: ${response.statusCode}');
    }

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return DashboardResponse.fromJson(data);
  }

  Future<ServerDetail> getServer(int id) async {
    final response = await http.get(
      Uri.parse(_buildUrl('/api/servers/$id')),
      headers: _headers,
    );

    if (response.statusCode == 401) {
      throw UnauthenticatedException();
    }

    if (response.statusCode != 200) {
      throw ApiException('Failed to load server: ${response.statusCode}');
    }

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return ServerDetail.fromJson(data);
  }

  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse(_buildUrl('/api/auth/logout')),
        headers: _headers,
      );
    } catch (_) {
      // Ignore network errors during logout
    }
    baseUrl = null;
    token = null;
    await _storage.delete(key: 'ned_base_url');
    await _storage.delete(key: 'ned_token');
  }
}

class UnauthenticatedException implements Exception {
  @override
  String toString() => 'Session expired. Please log in again.';
}

class ApiException implements Exception {
  final String message;
  ApiException(this.message);

  @override
  String toString() => message;
}
