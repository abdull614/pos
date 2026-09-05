import 'dart:typed_data';

import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import 'package:http/http.dart' as http;
import 'package:image/image.dart' as img;

import '../utils/format_helper.dart';

/// Service singleton untuk mengelola koneksi & pencetakan printer thermal
/// Bluetooth. Pakai package: print_bluetooth_thermal + esc_pos_utils_plus
///
/// Tambahkan di pubspec.yaml:
///   print_bluetooth_thermal: ^1.1.1
///   esc_pos_utils_plus: ^2.0.4
///   shared_preferences: ^2.3.0
///   permission_handler: ^12.0.3
///   image: ^4.9.2
///
/// Izin Android (android/app/src/main/AndroidManifest.xml), taruh di atas <application>:
///   <uses-permission android:name="android.permission.BLUETOOTH" android:maxSdkVersion="30"/>
///   <uses-permission android:name="android.permission.BLUETOOTH_ADMIN" android:maxSdkVersion="30"/>
///   <uses-permission android:name="android.permission.BLUETOOTH_CONNECT"/>
///   <uses-permission android:name="android.permission.BLUETOOTH_SCAN"/>
///   <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
class PrinterService {
  PrinterService._();
  static final PrinterService instance = PrinterService._();

  static const _prefKeyMac = 'thermal_printer_mac';
  static const _prefKeyName = 'thermal_printer_name';

  String? savedMac;
  String? savedName;

  bool get hasSavedPrinter => savedMac != null && savedMac!.isNotEmpty;

  // ───────────────────────────────────────────
  // Penyimpanan printer terpilih
  // ───────────────────────────────────────────

  /// Panggil sekali saat app start, atau otomatis dipanggil di dalam
  /// ensureConnected() kalau belum pernah dimuat.
  Future<void> loadSavedPrinter() async {
    final prefs = await SharedPreferences.getInstance();
    savedMac = prefs.getString(_prefKeyMac);
    savedName = prefs.getString(_prefKeyName);
  }

  Future<void> _savePrinter(String mac, String name) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefKeyMac, mac);
    await prefs.setString(_prefKeyName, name);
    savedMac = mac;
    savedName = name;
  }

  // ───────────────────────────────────────────
  // Izin & status koneksi (dipakai oleh PrinterSettingsScreen)
  // ───────────────────────────────────────────

  /// Minta izin Bluetooth runtime (wajib di Android 12+ / SDK 31+).
  Future<bool> requestPermissions() async {
    // Permission.location dan Permission.bluetooth (legacy) diminta
    // best-effort saja, TIDAK dijadikan syarat wajib. Alasannya: di
    // AndroidManifest.xml, keduanya didaftarkan dengan
    // android:maxSdkVersion="30" (hanya untuk Android ≤ 11). Di Android
    // 12+ (SDK 31+), izin ini memang tidak pernah didaftarkan ke sistem,
    // jadi statusnya akan SELALU "denied" walau Bluetooth-nya sendiri
    // sudah diizinkan lewat bluetoothConnect/bluetoothScan (mis.
    // "Perangkat di sekitar" di HyperOS/MIUI/Android 12+ lainnya).
    //
    // Izin yang benar-benar dipakai & wajib di Android 12+ hanya
    // bluetoothConnect dan bluetoothScan — itu yang kita jadikan syarat.
    await Permission.location.request();
    await Permission.bluetooth.request();

    final statuses = await [
      Permission.bluetoothConnect,
      Permission.bluetoothScan,
    ].request();

    return statuses.values.every(
      (status) => status.isGranted || status.isLimited,
    );
  }

  Future<bool> isBluetoothEnabled() async {
    return await PrintBluetoothThermal.bluetoothEnabled;
  }

  /// Ambil daftar printer Bluetooth yang sudah pernah dipasangkan (paired)
  /// lewat Settings > Bluetooth bawaan HP.
  Future<List<BluetoothInfo>> getPairedPrinters() async {
    return await PrintBluetoothThermal.pairedBluetooths;
  }

  /// Sambungkan ke printer berdasarkan MAC address, lalu simpan sebagai
  /// printer default untuk pemakaian berikutnya.
  Future<bool> connect(String macAddress, {String name = ''}) async {
    final connected = await PrintBluetoothThermal.connect(
      macPrinterAddress: macAddress,
    );
    if (connected) {
      await _savePrinter(macAddress, name);
    }
    return connected;
  }

  Future<bool> get isConnected async =>
      await PrintBluetoothThermal.connectionStatus;

  Future<void> disconnect() async {
    await PrintBluetoothThermal.disconnect;
  }

  Future<bool> printBytes(List<int> bytes) async {
    return await PrintBluetoothThermal.writeBytes(bytes);
  }

  // ───────────────────────────────────────────
  // API STATIS — dipakai langsung dari struk_bottom_sheet.dart
  // ───────────────────────────────────────────

  /// Pastikan printer siap dipakai: minta izin, cek Bluetooth aktif, cek
  /// ada printer tersimpan, lalu connect kalau belum tersambung.
  /// Return true kalau siap cetak, false kalau ada yang belum beres
  /// (silakan arahkan user ke halaman Pengaturan Printer).
  static Future<bool> ensureConnected() async {
    final izinOk = await instance.requestPermissions();
    if (!izinOk) return false;

    if (!await instance.isBluetoothEnabled()) return false;

    if (instance.savedMac == null) {
      await instance.loadSavedPrinter();
    }
    if (!instance.hasSavedPrinter) return false;

    if (await instance.isConnected) return true;

    return await instance.connect(
      instance.savedMac!,
      name: instance.savedName ?? '',
    );
  }

  /// Bangun struk dalam format ESC/POS lalu kirim ke printer aktif.
  /// Panggil ensureConnected() dulu sebelum ini. Return true kalau berhasil
  /// dikirim ke printer.
  static Future<bool> printReceipt({
    required String storeName,
    required String invoiceNumber,
    required DateTime date,
    required List<Map<String, dynamic>> items,
    required int total,
    required String paymentMethod,
    int? paidAmount,
    String? address,
    String? phone,
    String? logoUrl,
    PaperSize paperSize = PaperSize.mm58,
  }) async {
    final profile = await CapabilityProfile.load();
    final generator = Generator(paperSize, profile);
    List<int> bytes = [];

    // Logo toko (opsional). Kalau gagal diunduh/decode, lanjut cetak tanpa logo.
    if (logoUrl != null && logoUrl.isNotEmpty) {
      try {
        final response = await http.get(Uri.parse(logoUrl));
        final decoded = img.decodeImage(response.bodyBytes);
        if (decoded != null) {
          final resized = img.copyResize(decoded, width: 200);
          bytes += generator.image(resized);
        }
      } catch (_) {
        // abaikan, tetap lanjut cetak struk tanpa logo
      }
    }

    // Header toko
    bytes += generator.text(
      storeName,
      styles: const PosStyles(
        align: PosAlign.center,
        bold: true,
        height: PosTextSize.size2,
        width: PosTextSize.size2,
      ),
    );
    if (address != null && address.isNotEmpty) {
      bytes += generator.text(address, styles: const PosStyles(align: PosAlign.center));
    }
    if (phone != null && phone.isNotEmpty) {
      bytes += generator.text('Telp: $phone', styles: const PosStyles(align: PosAlign.center));
    }
    bytes += generator.hr();

    // Info transaksi
    bytes += generator.row([
      PosColumn(text: 'No. Struk', width: 5, styles: const PosStyles(bold: true)),
      PosColumn(text: invoiceNumber, width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.row([
      PosColumn(text: 'Tanggal', width: 5),
      PosColumn(text: _formatTanggal(date), width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.row([
      PosColumn(text: 'Metode', width: 5),
      PosColumn(text: _labelMetode(paymentMethod), width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.hr();

    // Daftar item
    for (final item in items) {
      final nama = (item['name'] ?? '-').toString();
      final qty = item['qty'] as int;
      final harga = double.parse(item['selling_price'].toString());
      final subtotal = harga * qty;

      bytes += generator.text(nama, styles: const PosStyles(bold: true));
      bytes += generator.row([
        PosColumn(text: '$qty x ${formatRupiah(harga)}', width: 7),
        PosColumn(text: formatRupiah(subtotal), width: 5, styles: const PosStyles(align: PosAlign.right)),
      ]);
    }

    bytes += generator.hr();

    // Total
    bytes += generator.row([
      PosColumn(text: 'TOTAL', width: 6, styles: const PosStyles(bold: true, height: PosTextSize.size2)),
      PosColumn(
        text: formatRupiah(total.toDouble()),
        width: 6,
        styles: const PosStyles(align: PosAlign.right, bold: true, height: PosTextSize.size2),
      ),
    ]);

    if (paymentMethod == 'cash' && paidAmount != null) {
      final kembalian = paidAmount - total;
      bytes += generator.row([
        PosColumn(text: 'Bayar', width: 6),
        PosColumn(text: formatRupiah(paidAmount.toDouble()), width: 6, styles: const PosStyles(align: PosAlign.right)),
      ]);
      bytes += generator.row([
        PosColumn(text: 'Kembali', width: 6),
        PosColumn(text: formatRupiah(kembalian.toDouble()), width: 6, styles: const PosStyles(align: PosAlign.right)),
      ]);
    }

    if (paymentMethod == 'debt') {
      bytes += generator.text(
        '*** BELUM LUNAS / UTANG ***',
        styles: const PosStyles(align: PosAlign.center, bold: true),
      );
    }

    bytes += generator.hr();
    bytes += generator.text(
      'Terima kasih telah berbelanja',
      styles: const PosStyles(align: PosAlign.center),
    );
    bytes += generator.feed(2);
    bytes += generator.cut();

    return await instance.printBytes(bytes);
  }

  static String _formatTanggal(DateTime dt) {
    String dua(int n) => n.toString().padLeft(2, '0');
    return '${dua(dt.day)}/${dua(dt.month)}/${dt.year} ${dua(dt.hour)}:${dua(dt.minute)}';
  }

  static String _labelMetode(String type) {
    switch (type) {
      case 'cash':
        return 'Tunai';
      case 'transfer':
        return 'Transfer Bank';
      case 'qris':
        return 'QRIS';
      case 'debt':
        return 'Utang';
      default:
        return type;
    }
  }
}