// lib/features/transaksi/struk_bottom_sheet.dart

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'struk_model.dart';
import '../../utils/format_helper.dart';
import '../../services/printer_service.dart';
import '../../services/store_setting_service.dart';
import '../../models/store_setting_model.dart';
import '../../screens/printer_settings_screen.dart';

class StrukBottomSheet extends StatefulWidget {
  final StrukModel struk;
  const StrukBottomSheet({super.key, required this.struk});

  @override
  State<StrukBottomSheet> createState() => _StrukBottomSheetState();
}

class _StrukBottomSheetState extends State<StrukBottomSheet> {
  StoreSettingModel? _store;
  bool _isPrinting = false;
  bool _storeLoaded = false;

  @override
  void initState() {
    super.initState();
    _loadStore();
  }

  Future<void> _loadStore() async {
    try {
      final store = await StoreSettingService.getStoreSetting();
      if (mounted) setState(() { _store = store; _storeLoaded = true; });
    } catch (e) {
      debugPrint('STORE ERROR: $e');
      if (mounted) setState(() => _storeLoaded = true);
    }
  }

  Future<void> _printReceipt() async {
    if (_isPrinting) return;
    setState(() => _isPrinting = true);

    final terhubung = await PrinterService.ensureConnected();

    if (!terhubung) {
      if (mounted) setState(() => _isPrinting = false);

      final goToSettings = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Printer Tidak Terhubung'),
          content: const Text(
            'Silakan sambungkan printer Bluetooth terlebih dahulu.\n\n'
            'Pastikan printer sudah dinyalakan dan terpasang.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Buka Pengaturan'),
            ),
          ],
        ),
      );
      if (goToSettings == true && mounted) {
        await Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const PrinterSettingsScreen()),
        );
      }
      return;
    }

    try {
      final success = await PrinterService.printReceipt(
        storeName:     _store?.storeName ?? 'Toko Sembako',
        invoiceNumber: widget.struk.nomorStruk,
        date:          widget.struk.waktu,
        items:         widget.struk.items,
        total:         widget.struk.totalHarga,
        paymentMethod: widget.struk.paymentType,
        paidAmount:    widget.struk.bayar,
        address:       _store?.address,
        phone:         _store?.phone,
        logoUrl:       _store?.logo,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? '✅ Struk berhasil dicetak' : '❌ Gagal mencetak'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isPrinting = false);
    }
  }

  String _getInitials(String name) {
    final words = name.trim().split(RegExp(r'\s+'));
    if (words.length >= 2) return '${words[0][0]}${words[1][0]}'.toUpperCase();
    return name.substring(0, name.length >= 2 ? 2 : 1).toUpperCase();
  }

  Widget _initialsLogo(String name) {
    return Container(
      width: 72, height: 72,
      decoration: BoxDecoration(
        color: const Color(0xFF059669),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Center(
        child: Text(
          _getInitials(name),
          style: const TextStyle(
            color: Colors.white, fontSize: 26,
            fontWeight: FontWeight.bold, letterSpacing: 1,
          ),
        ),
      ),
    );
  }

  String _labelMetode(String type) {
    switch (type) {
      case 'cash':     return 'Tunai';
      case 'transfer': return 'Transfer Bank';
      case 'qris':     return 'QRIS';
      case 'debt':     return 'Utang';
      default:         return type;
    }
  }

  @override
  Widget build(BuildContext context) {
    final struk = widget.struk;
    final storeName = _store?.storeName ?? 'Toko Sembako';
    final hasLogo = _store?.logo != null && _store!.logo!.isNotEmpty;

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Container(
              width: 40, height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFE5E7EB),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),
            if (!_storeLoaded)
              const SizedBox(
                height: 80,
                child: Center(
                  child: CircularProgressIndicator(
                    color: Color(0xFF059669), strokeWidth: 2,
                  ),
                ),
              )
            else ...[
              hasLogo
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: Image.network(
                        _store!.logo!,
                        width: 72, height: 72, fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => _initialsLogo(storeName),
                        loadingBuilder: (_, child, progress) {
                          if (progress == null) return child;
                          return _initialsLogo(storeName);
                        },
                      ),
                    )
                  : _initialsLogo(storeName),
              const SizedBox(height: 10),
              Text(storeName,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.bold,
                      color: Color(0xFF111827))),
              if (_store?.address != null && _store!.address!.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  _store!.address!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280)),
                  softWrap: true,
                  maxLines: 4,
                  overflow: TextOverflow.visible,
                ),
              ],
              if (_store?.phone != null && _store!.phone!.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text('Telp: ${_store!.phone}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280))),
              ],
              const SizedBox(height: 16),
              const Divider(color: Color(0xFFE5E7EB)),
              const SizedBox(height: 14),
            ],
            Container(
              width: 64, height: 64,
              decoration: BoxDecoration(
                color: const Color(0xFFD1FAE5),
                borderRadius: BorderRadius.circular(32),
              ),
              child: const Icon(Icons.check_circle_outline,
                  color: Color(0xFF059669), size: 36),
            ),
            const SizedBox(height: 10),
            const Text('Pembayaran Berhasil!',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold,
                    color: Color(0xFF111827))),
            const SizedBox(height: 4),
            Text('No. Struk: ${struk.nomorStruk}',
                style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280))),
            Text(DateFormat('dd MMM yyyy, HH:mm').format(struk.waktu),
                style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280))),
            const SizedBox(height: 20),
            const Divider(color: Color(0xFFE5E7EB)),
            const SizedBox(height: 8),
            ConstrainedBox(
              constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.28),
              child: ListView.separated(
                shrinkWrap: true,
                itemCount: struk.items.length,
                separatorBuilder: (_, __) => const SizedBox(height: 8),
                itemBuilder: (_, i) {
                  final item = struk.items[i];
                  final nama  = item['name'] ?? '-';
                  final qty   = item['qty'] as int;
                  final harga = double.parse(item['selling_price'].toString());
                  return Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(nama,
                                style: const TextStyle(
                                    fontSize: 13, fontWeight: FontWeight.w600)),
                            Text('${formatRupiah(harga)} × $qty',
                                style: const TextStyle(
                                    fontSize: 11, color: Color(0xFF6B7280))),
                          ],
                        ),
                      ),
                      Text(formatRupiah(harga * qty),
                          style: const TextStyle(
                              fontSize: 13, fontWeight: FontWeight.w600)),
                    ],
                  );
                },
              ),
            ),
            const SizedBox(height: 12),
            const Divider(color: Color(0xFFE5E7EB)),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerLeft,
              child: Column(
                children: [
                  _StrukRow(
                      label: 'Total',
                      value: formatRupiah(struk.totalHarga.toDouble()),
                      bold: true),
                  const SizedBox(height: 6),
                  _StrukRow(label: 'Metode', value: _labelMetode(struk.paymentType)),
                  if (struk.paymentType == 'cash' && struk.bayar != null) ...[
                    const SizedBox(height: 6),
                    _StrukRow(label: 'Bayar',
                        value: formatRupiah(struk.bayar!.toDouble())),
                    const SizedBox(height: 6),
                    _StrukRow(
                        label: 'Kembalian',
                        value: formatRupiah(struk.kembalian.toDouble()),
                        valueColor: const Color(0xFF059669),
                        bold: true),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _isPrinting ? null : _printReceipt,
                icon: _isPrinting
                    ? const SizedBox(
                        width: 20, height: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.print, size: 18),
                label: Text(_isPrinting ? 'Mencetak...' : 'Cetak Struk'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                ),
                child: const Text('Tutup',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StrukRow extends StatelessWidget {
  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;

  const _StrukRow({
    required this.label,
    required this.value,
    this.bold = false,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: const TextStyle(fontSize: 13, color: Color(0xFF6B7280))),
        Text(value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: bold ? FontWeight.bold : FontWeight.normal,
              color: valueColor ?? const Color(0xFF111827),
            )),
      ],
    );
  }
}