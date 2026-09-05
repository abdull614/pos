import 'dart:typed_data';
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';
import '../../utils/format_helper.dart';
import 'struk_model.dart';
import '../../services/printer_service.dart';

/// Membangun byte ESC/POS dari StrukModel, lalu mengirimkannya ke printer
/// lewat PrinterService.
///
/// Sesuaikan field di bawah (namaToko, alamatToko, dst.) dengan data toko
/// milik user — idealnya diambil dari pengaturan/profil toko, bukan hardcode.
class StrukPrinterHelper {
  static const String namaToko = 'Toko Sembako Berkah';
  static const String alamatToko = 'Jl. Contoh No. 123, Surakarta';
  static const String teleponToko = '0812-xxxx-xxxx';

  /// paperSize: PaperSize.mm58 untuk printer 58mm, PaperSize.mm80 untuk 80mm.
  static Future<List<int>> buildReceiptBytes(
    StrukModel struk, {
    PaperSize paperSize = PaperSize.mm58,
  }) async {
    final profile = await CapabilityProfile.load();
    final generator = Generator(paperSize, profile);
    final List<int> bytes = [];

    // Header toko
    bytes += generator.text(
      namaToko,
      styles: const PosStyles(align: PosAlign.center, bold: true, height: PosTextSize.size2, width: PosTextSize.size2),
    );
    bytes += generator.text(alamatToko, styles: const PosStyles(align: PosAlign.center));
    bytes += generator.text(teleponToko, styles: const PosStyles(align: PosAlign.center));
    bytes += generator.hr();

    // Info transaksi
    bytes += generator.row([
      PosColumn(text: 'No. Struk', width: 5, styles: const PosStyles(bold: true)),
      PosColumn(text: struk.nomorStruk, width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.row([
      PosColumn(text: 'Tanggal', width: 5),
      PosColumn(text: _formatTanggal(struk.waktu), width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.row([
      PosColumn(text: 'Metode', width: 5),
      PosColumn(text: _labelMetode(struk.paymentType), width: 7, styles: const PosStyles(align: PosAlign.right)),
    ]);
    bytes += generator.hr();

    // Daftar item
    for (final item in struk.items) {
      final nama = (item['name'] ?? '-').toString();
      final qty = item['qty'] as int;
      final harga = double.parse(item['selling_price'].toString());
      final subtotal = harga * qty;

      bytes += generator.text(nama, styles: const PosStyles(bold: true));
      bytes += generator.row([
        PosColumn(
          text: '$qty x ${formatRupiah(harga)}',
          width: 7,
          styles: const PosStyles(align: PosAlign.left),
        ),
        PosColumn(
          text: formatRupiah(subtotal),
          width: 5,
          styles: const PosStyles(align: PosAlign.right),
        ),
      ]);
    }

    bytes += generator.hr();

    // Total
    bytes += generator.row([
      PosColumn(text: 'TOTAL', width: 6, styles: const PosStyles(bold: true, height: PosTextSize.size2)),
      PosColumn(
        text: formatRupiah(struk.totalHarga.toDouble()),
        width: 6,
        styles: const PosStyles(align: PosAlign.right, bold: true, height: PosTextSize.size2),
      ),
    ]);

    if (struk.paymentType == 'cash' && struk.bayar != null) {
      bytes += generator.row([
        PosColumn(text: 'Bayar', width: 6),
        PosColumn(text: formatRupiah(struk.bayar!.toDouble()), width: 6, styles: const PosStyles(align: PosAlign.right)),
      ]);
      bytes += generator.row([
        PosColumn(text: 'Kembali', width: 6),
        PosColumn(text: formatRupiah(struk.kembalian.toDouble()), width: 6, styles: const PosStyles(align: PosAlign.right)),
      ]);
    }

    if (struk.paymentType == 'debt') {
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

    return bytes;
  }

  /// Panggil ini dari UI (mis. tombol "Cetak Struk" di StrukBottomSheet).
  /// Melempar Exception dengan pesan siap-tampil kalau gagal — tangkap di
  /// pemanggil dan tampilkan lewat SnackBar.
  static Future<void> cetak(StrukModel struk, {PaperSize paperSize = PaperSize.mm58}) async {
    final bytes = await buildReceiptBytes(struk, paperSize: paperSize);
    await PrinterService.instance.connectAndPrint(bytes);
  }

  static String _formatTanggal(DateTime dt) {
    String dua(int n) => n.toString().padLeft(2, '0');
    return '${dua(dt.day)}/${dua(dt.month)}/${dt.year} ${dua(dt.hour)}:${dua(dt.minute)}';
  }

  static String _labelMetode(String type) {
    switch (type) {
      case 'cash':
        return 'Tunai';
      case 'qris':
        return 'QRIS/Debit';
      case 'debt':
        return 'Utang';
      default:
        return type;
    }
  }
}