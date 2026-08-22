// lib/features/auth/auth_service.dart
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/api/api_service.dart';

class AuthService {
  // Login → simpan token ke SharedPreferences
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await ApiService.post(
      'auth/login',
      {'email': email, 'password': password},
      withToken: false, // login tidak butuh token
    );

    final token = response['token'] as String?;
    final user  = response['user']  as Map<String, dynamic>?;

    if (token == null) throw Exception('Token tidak ditemukan');

    // Simpan token & role
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
    await prefs.setString('user_role', user?['role'] ?? '');
    await prefs.setString('user_name', user?['name'] ?? '');

    return response;
  }

  static Future<void> logout() async {
    await ApiService.post('auth/logout', {});
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear(); // hapus semua data lokal
  }

  static Future<String?> getRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('user_role');
  }

  static Future<bool> isLoggedIn() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token') != null;
  }
}
