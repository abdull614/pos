// lib/services/permission_service.dart
//
// ⚠️ FIX: request permission satu per satu (bukan sekaligus dalam List)
// dengan jeda kecil antar permintaan. Ini mengurangi kemungkinan crash
// "Failure delivering result... NullPointerException" yang terjadi di
// beberapa HP MIUI (Xiaomi/Redmi/POCO) saat activity sempat di-recycle
// oleh sistem selagi dialog izin tampil.

import 'package:flutter/foundation.dart';
import 'package:permission_handler/permission_handler.dart';

class PermissionService {
  static const _bluetoothPermissions = [
    Permission.bluetoothScan,
    Permission.bluetoothConnect,
    Permission.location,
  ];

  static Future<bool> ensureBluetoothPermissions() async {
    bool allGranted = true;

    for (final permission in _bluetoothPermissions) {
      try {
        final status = await permission.request();
        // Jeda kecil supaya activity punya waktu "settle" sebelum
        // request izin berikutnya ditembakkan.
        await Future.delayed(const Duration(milliseconds: 200));
        if (!status.isGranted) {
          debugPrint('[PERMISSION] $permission ditolak: $status');
          allGranted = false;
        }
      } catch (e) {
        debugPrint('[PERMISSION] Error request $permission: $e');
        allGranted = false;
      }
    }

    return allGranted;
  }

  static Future<bool> anyPermanentlyDenied() async {
    for (final p in _bluetoothPermissions) {
      if (await p.isPermanentlyDenied) return true;
    }
    return false;
  }
}