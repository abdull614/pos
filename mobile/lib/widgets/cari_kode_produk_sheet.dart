import 'package:flutter/material.dart';
import '../screens/scanner/barcode_scanner_screen.dart';

// Bottom sheet untuk mencari produk berdasarkan kode: bisa diketik manual
// atau di-scan lewat kamera. Mengembalikan String kode produk lewat
// Navigator.pop(context, kode) -> null kalau dibatalkan.
class CariKodeProdukSheet extends StatefulWidget {
  const CariKodeProdukSheet({super.key});

  @override
  State<CariKodeProdukSheet> createState() => _CariKodeProdukSheetState();
}

class _CariKodeProdukSheetState extends State<CariKodeProdukSheet> {
  final TextEditingController _kodeController = TextEditingController();

  @override
  void dispose() {
    _kodeController.dispose();
    super.dispose();
  }

  Future<void> _bukaScanner() async {
    final hasil = await Navigator.push<String>(
      context,
      MaterialPageRoute(builder: (_) => const BarcodeScannerScreen()),
    );
    if (hasil != null && hasil.isNotEmpty && mounted) {
      Navigator.pop(context, hasil);
    }
  }

  void _submitManual() {
    final kode = _kodeController.text.trim();
    if (kode.isEmpty) return;
    Navigator.pop(context, kode);
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE5E7EB),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Cari Produk dengan Kode',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Color(0xFF111827),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF3F4F6),
                borderRadius: BorderRadius.circular(12),
              ),
              child: TextField(
                controller: _kodeController,
                autofocus: true,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _submitManual(),
                decoration: const InputDecoration(
                  hintText: 'Ketik kode/barcode produk...',
                  hintStyle: TextStyle(color: Color(0xFF9CA3AF)),
                  prefixIcon: Icon(Icons.numbers, color: Color(0xFF9CA3AF)),
                  border: InputBorder.none,
                  contentPadding: EdgeInsets.symmetric(vertical: 14, horizontal: 4),
                ),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _submitManual,
                icon: const Icon(Icons.search, size: 18),
                label: const Text('Cari Produk'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: const [
                Expanded(child: Divider(color: Color(0xFFE5E7EB))),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 10),
                  child: Text('atau', style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 12)),
                ),
                Expanded(child: Divider(color: Color(0xFFE5E7EB))),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _bukaScanner,
                icon: const Icon(Icons.qr_code_scanner, size: 20),
                label: const Text('Scan dengan Kamera'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF059669),
                  side: const BorderSide(color: Color(0xFF059669)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
