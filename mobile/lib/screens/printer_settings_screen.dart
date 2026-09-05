import 'package:flutter/material.dart';
import 'package:print_bluetooth_thermal/print_bluetooth_thermal.dart';
import '../services/printer_service.dart';

/// Halaman untuk memilih printer thermal Bluetooth.
/// Catatan penting: printer harus sudah di-pairing lebih dulu lewat
/// Settings > Bluetooth bawaan HP (masukkan PIN printer, biasanya 0000
/// atau 1234) — baru printer akan muncul di daftar ini.
class PrinterSettingsScreen extends StatefulWidget {
  const PrinterSettingsScreen({super.key});

  @override
  State<PrinterSettingsScreen> createState() => _PrinterSettingsScreenState();
}

class _PrinterSettingsScreenState extends State<PrinterSettingsScreen> {
  List<BluetoothInfo> _daftarPrinter = [];
  bool _loading = true;
  bool _connecting = false;
  String? _macTerpilih;

  @override
  void initState() {
    super.initState();
    _muatDaftarPrinter();
  }

  Future<void> _muatDaftarPrinter() async {
    setState(() => _loading = true);

    final izinOk = await PrinterService.instance.requestPermissions();
    if (!izinOk) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Izin Bluetooth ditolak. Aktifkan lewat Pengaturan > Aplikasi untuk bisa memilih printer.')),
        );
      }
      setState(() => _loading = false);
      return;
    }

    final aktif = await PrinterService.instance.isBluetoothEnabled();
    if (!aktif) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Aktifkan Bluetooth di HP terlebih dahulu')),
        );
      }
      setState(() => _loading = false);
      return;
    }
    final list = await PrinterService.instance.getPairedPrinters();
    await PrinterService.instance.loadSavedPrinter();
    setState(() {
      _daftarPrinter = list;
      _macTerpilih = PrinterService.instance.savedMac;
      _loading = false;
    });
  }

  Future<void> _pilihPrinter(BluetoothInfo printer) async {
    setState(() => _connecting = true);
    final ok = await PrinterService.instance.connect(
      printer.macAdress,
      name: printer.name,
    );
    setState(() {
      _connecting = false;
      if (ok) _macTerpilih = printer.macAdress;
    });
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ok
              ? 'Terhubung ke ${printer.name}'
              : 'Gagal terhubung ke ${printer.name}'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pilih Printer Struk'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loading ? null : _muatDaftarPrinter,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _daftarPrinter.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.print_disabled, size: 48, color: Colors.grey),
                        const SizedBox(height: 12),
                        const Text(
                          'Belum ada printer yang di-pairing.\n'
                          'Buka Pengaturan > Bluetooth di HP, pairing dulu dengan printer struk, lalu tekan refresh di sini.',
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                )
              : ListView.separated(
                  itemCount: _daftarPrinter.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, i) {
                    final p = _daftarPrinter[i];
                    final terpilih = p.macAdress == _macTerpilih;
                    return ListTile(
                      leading: const Icon(Icons.print),
                      title: Text(p.name.isEmpty ? '(Tanpa nama)' : p.name),
                      subtitle: Text(p.macAdress),
                      trailing: terpilih
                          ? const Icon(Icons.check_circle, color: Colors.green)
                          : (_connecting ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : null),
                      onTap: _connecting ? null : () => _pilihPrinter(p),
                    );
                  },
                ),
    );
  }
}