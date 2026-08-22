// lib/features/produk/tambah_produk_baru.dart
import 'dart:io';
import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mobile/screens/scanner/barcode_scanner_screen.dart';

import '../../models/produk.dart';
import '../../core/api/api_service.dart'; // tetap diimport jika nanti diperlukan

class FormTambahProduk extends StatefulWidget {
  final void Function(Produk produk)? onProdukSudahAda;
  final List<Produk> semuaProduk;

  const FormTambahProduk({
    required this.semuaProduk,
    this.onProdukSudahAda,
    super.key,
  });

  @override
  State<FormTambahProduk> createState() => _FormTambahProdukState();
}

class _FormTambahProdukState extends State<FormTambahProduk> {
  static const Color hijauUtama = Color(0xFF059669);
  static const Color abukuMuda = Color(0xFFF3F4F6);

  final _formKey = GlobalKey<FormState>();
  final _namaCtrl = TextEditingController();
  final _hargaCtrl = TextEditingController();
  final _hargaBeliCtrl = TextEditingController();
  final _stokCtrl = TextEditingController();
  final _deskripsiCtrl = TextEditingController();
  final _barcodeCtrl = TextEditingController();

  String? _selectedSatuan;
  String? _selectedCategoryId;
  List<Map<String, dynamic>> _kategoriList = [];
  bool _loadingKategori = false;
  String? _kategoriError;
  bool _isAddingCategory = false;

  String _sku = '';
  bool _isSaving = false;

  File? _imageFile;
  final ImagePicker _picker = ImagePicker();

  static const List<String> _listSatuan = [
    'pcs',
    'kg',
    'gram',
    'liter',
    'ml',
    'botol',
    'dus',
    'karton',
    'lusin',
    'meter',
  ];

  @override
  void initState() {
    super.initState();
    _fetchKategori();
    _namaCtrl.addListener(_onNamaChanged);
  }

  @override
  void dispose() {
    _namaCtrl.removeListener(_onNamaChanged);
    _namaCtrl.dispose();
    _hargaCtrl.dispose();
    _hargaBeliCtrl.dispose();
    _stokCtrl.dispose();
    _deskripsiCtrl.dispose();
    _barcodeCtrl.dispose();
    super.dispose();
  }

  void _onNamaChanged() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _updateSku();
    });
  }

  String _singkat(String teks) {
    if (teks.trim().isEmpty) return '';
    final kata = teks.trim().split(RegExp(r'\s+'));
    if (kata.length == 1) {
      return kata[0].substring(0, kata[0].length.clamp(0, 3)).toUpperCase();
    }
    return kata
        .take(3)
        .map((k) => k.isNotEmpty ? k[0].toUpperCase() : '')
        .join();
  }

  void _updateSku() {
    final kategoriNama = _getSelectedCategoryName();
    final singkatKategori = _singkat(kategoriNama);
    final singkatNama = _singkat(_namaCtrl.text);

    if (singkatKategori.isEmpty && singkatNama.isEmpty) {
      if (_sku.isNotEmpty) {
        setState(() => _sku = '');
      }
      return;
    }

    final rand = Random();
    final angka = rand.nextInt(90000) + 10000;
    final parts = [
      if (singkatKategori.isNotEmpty) singkatKategori,
      if (singkatNama.isNotEmpty) singkatNama,
      '$angka',
    ];
    final newPrefix = parts.take(parts.length - 1).join('-');
    final oldPrefix =
        _sku.contains('-') ? _sku.substring(0, _sku.lastIndexOf('-')) : '';
    if (newPrefix != oldPrefix || _sku.isEmpty) {
      setState(() => _sku = parts.join('-'));
    }
  }

  String _generateSku() {
    final kategoriNama = _getSelectedCategoryName();
    final singkatKategori = _singkat(kategoriNama);
    final singkatNama = _singkat(_namaCtrl.text);
    final rand = Random();
    final angka = rand.nextInt(90000) + 10000;
    final parts = [
      if (singkatKategori.isNotEmpty) singkatKategori,
      if (singkatNama.isNotEmpty) singkatNama,
      '$angka',
    ];
    return parts.join('-');
  }

  String _getSelectedCategoryName() {
    for (var cat in _kategoriList) {
      if (cat['id'] == _selectedCategoryId) return cat['name'] as String;
    }
    return '';
  }

  Future<void> _fetchKategori() async {
    setState(() {
      _loadingKategori = true;
      _kategoriError = null;
    });
    try {
      final response = await ApiService.get('categories');
      List data = [];
      if (response is List) {
        data = response;
      } else if (response is Map) {
        data = response['data'] ?? response['categories'] ?? [];
      }

      if (data.isEmpty) {
        setState(() => _kategoriError =
            'Tidak ada kategori. Silakan buat kategori terlebih dahulu.');
      } else {
        setState(() {
          _kategoriList = data
              .map((e) => {
                    'id': e['id'].toString(),
                    'name': e['name'].toString(),
                  })
              .toList();
        });
      }
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      setState(() => _kategoriError = msg.contains('403')
          ? 'Akses ditolak. Hubungi admin untuk menambah kategori.'
          : 'Gagal memuat kategori: $msg');
    } finally {
      setState(() => _loadingKategori = false);
    }
  }

  Future<void> _addCategory(String name) async {
    setState(() => _isAddingCategory = true);
    try {
      final response = await ApiService.post('categories', {'name': name});
      final newCategory = {
        'id': response['id'].toString(),
        'name': response['name'].toString(),
      };
      setState(() {
        _kategoriList.add(newCategory);
        _selectedCategoryId = newCategory['id'];
        if (_kategoriError != null) _kategoriError = null;
      });
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _updateSku();
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Kategori berhasil ditambahkan'),
              backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      final msg = e.toString().replaceFirst('Exception: ', '');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(msg.contains('403')
              ? 'Akses ditolak. Hanya admin yang dapat menambah kategori.'
              : 'Gagal menambah kategori: $msg'),
          backgroundColor: Colors.red,
        ));
      }
    } finally {
      if (mounted) setState(() => _isAddingCategory = false);
    }
  }

  void _showAddCategoryDialog() {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Tambah Kategori Baru'),
        content: TextField(
          controller: controller,
          autofocus: true,
          decoration: const InputDecoration(
              hintText: 'Nama kategori', border: OutlineInputBorder()),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              final name = controller.text.trim();
              if (name.isNotEmpty) {
                Navigator.pop(context);
                await _addCategory(name);
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    final picked = await _picker.pickImage(source: source);
    if (picked != null) {
      setState(() => _imageFile = File(picked.path));
    }
  }

  void _showImagePickerDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Pilih dari Galeri'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.gallery);
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('Ambil Foto'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
          ],
        ),
      ),
    );
  }

  /// IMPORTANT: sesuai permintaan Anda, saat klik Simpan di form ini
  /// produk **tidak langsung disimpan ke DB**. Kita buat objek Produk
  /// lokal dan kembalikan ke pemanggil.

  Future<void> _bukaScanner() async {
    final hasil = await Navigator.push<String?>(
      context,
      MaterialPageRoute(builder: (_) => BarcodeScannerScreen()),
    );
    if (hasil != null && hasil.isNotEmpty && mounted) {
      setState(() => _barcodeCtrl.text = hasil); // <-- KOMEN: HASIL SCAN DISET KE FIELD BARCODE
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCategoryId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pilih kategori terlebih dahulu.')),
      );
      return;
    }
    setState(() => _isSaving = true);
    try {
      final nama = _namaCtrl.text.trim();
      final hargaJual = int.tryParse(_hargaCtrl.text.trim()) ?? 0;
      final hargaBeli = int.tryParse(_hargaBeliCtrl.text.trim()) ?? 0;
      final stok = int.tryParse(_stokCtrl.text.trim()) ?? 0;
      final kategoriNama = _getSelectedCategoryName();

      final produkBaru = Produk(
        id: null, // belum di DB
        sku: _sku.isEmpty ? _generateSku() : _sku,
        nama: nama,
        kategori: kategoriNama,
        categoryId: _selectedCategoryId ?? '',
        satuan: _selectedSatuan ?? '',
        stok: stok,
        imageUrl: _imageFile?.path, // lokal path (jika ingin upload nanti)
        hargaJual: hargaJual,
        hargaBeli: hargaBeli,
        deskripsi: _deskripsiCtrl.text.trim(),
        barcode: _barcodeCtrl.text.trim(),
      );

      // Kembalikan produkBaru ke pemanggil (tambah_produk_screen)
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Produk ditambahkan (lokal)'), backgroundColor: Colors.green),
        );

        // beri jeda kecil agar snackbar muncul
        await Future.delayed(const Duration(milliseconds: 200));
        Navigator.of(context).pop(produkBaru);
      }
    } catch (e, st) {
      setState(() => _isSaving = false);
      debugPrint('Error saat membuat produk lokal: $e\n$st');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal membuat produk: $e'), backgroundColor: Colors.red));
      }
    }
  }

  Widget _buildLabel(String text) => Text(text, style: const TextStyle(fontWeight: FontWeight.w600));

  Widget _buildField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboardType = TextInputType.text,
    List<TextInputFormatter>? inputFormatters,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      validator: validator,
      decoration: InputDecoration(
        hintText: hint,
        prefixIcon: Icon(icon, color: Colors.grey),
        filled: true,
        fillColor: abukuMuda,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
      ),
    );
  }

  Widget _buildDropdown({
    required String? value,
    required String hint,
    required IconData icon,
    required List<String> items,
    required void Function(String?) onChanged,
    String? Function(String?)? validator,
  }) {
    return DropdownButtonFormField<String>(
      initialValue: value,
      decoration: InputDecoration(
        hintText: hint,
        prefixIcon: Icon(icon, color: Colors.grey),
        filled: true,
        fillColor: abukuMuda,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
      ),
      items: items.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
      onChanged: onChanged,
      validator: validator,
    );
  }

  Widget _buildKategoriDropdown() {
    if (_loadingKategori) {
      return const SizedBox(height: 50, child: Center(child: CircularProgressIndicator()));
    }
    if (_kategoriError != null) {
      return Text(_kategoriError!, style: const TextStyle(color: Colors.red));
    }
    return DropdownButtonFormField<String>(
      initialValue: _selectedCategoryId,
      decoration: InputDecoration(
        hintText: 'Pilih kategori',
        filled: true,
        fillColor: abukuMuda,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
      ),
      items: _kategoriList
          .map((c) => DropdownMenuItem(value: c['id'].toString(), child: Text(c['name'].toString())))
          .toList(),
      onChanged: (v) {
        setState(() => _selectedCategoryId = v);
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) _updateSku();
        });
      },
      validator: (v) => v == null ? 'Pilih kategori' : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2)),
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                        color: const Color(0xFFD1FAE5),
                        borderRadius: BorderRadius.circular(10)),
                    child: const Icon(Icons.add_box_outlined, color: hijauUtama, size: 22),
                  ),
                  const SizedBox(width: 12),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Produk Baru',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87)),
                      Text('Isi data produk yang akan ditambahkan',
                          style: TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 24),
              _buildLabel('Nama Produk *'),
              const SizedBox(height: 6),
              TextFormField(
                controller: _namaCtrl,
                validator: (v) => v == null || v.trim().isEmpty ? 'Nama produk wajib diisi' : null,
                decoration: InputDecoration(
                  hintText: 'Nama produk',
                  filled: true,
                  fillColor: abukuMuda,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                ),
              ),
              const SizedBox(height: 16),
              _buildLabel('SKU'),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                decoration: BoxDecoration(
                  color: _sku.isEmpty ? const Color(0xFFF3F4F6) : const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Icon(Icons.qr_code_2_outlined, color: _sku.isEmpty ? Colors.grey : hijauUtama, size: 18),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _sku.isEmpty ? 'Isi nama & pilih kategori untuk generate SKU' : _sku,
                        style: TextStyle(
                          color: _sku.isEmpty ? Colors.grey : hijauUtama,
                          fontWeight: _sku.isEmpty ? FontWeight.normal : FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ),
                    if (_sku.isNotEmpty)
                      GestureDetector(
                        onTap: () => setState(() => _sku = _generateSku()),
                        child: const Icon(Icons.refresh_rounded, color: hijauUtama, size: 18),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              _buildLabel('Barcode'), // <-- KOMEN: LABEL FIELD BARCODE
              const SizedBox(height: 6),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _barcodeCtrl,
                      keyboardType: TextInputType.text,
                      decoration: InputDecoration(
                        hintText: 'Masukkan barcode secara manual',
                        prefixIcon: const Icon(Icons.numbers, color: Colors.grey),
                        filled: true,
                        fillColor: abukuMuda,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _bukaScanner, // <-- KOMEN: TOMBOL MEMBUKA SCANNER KAMERA
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFF3F4F6),
                        foregroundColor: Colors.grey.shade700,
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                      ),
                      child: const Icon(Icons.camera_alt, size: 22),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLabel('Harga Jual *'),
                        const SizedBox(height: 6),
                        _buildField(
                          controller: _hargaCtrl,
                          hint: '0',
                          icon: Icons.sell_outlined,
                          keyboardType: TextInputType.number,
                          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                          validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildLabel('Harga Beli *'),
                        const SizedBox(height: 6),
                        _buildField(
                          controller: _hargaBeliCtrl,
                          hint: '0',
                          icon: Icons.shopping_bag_outlined,
                          keyboardType: TextInputType.number,
                          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                          validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _buildLabel('Stok Awal *'),
              const SizedBox(height: 6),
              _buildField(
                controller: _stokCtrl,
                hint: '0',
                icon: Icons.inventory_2_outlined,
                keyboardType: TextInputType.number,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                validator: (v) => v == null || v.isEmpty ? 'Wajib diisi' : null,
              ),
              const SizedBox(height: 16),
              // Satuan & Kategori: berdampingan di layar lebar,
              // ditumpuk vertikal (Satuan di atas Kategori) di layar sempit.
              LayoutBuilder(
                builder: (context, constraints) {
                  final satuanWidget = Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildLabel('Satuan'),
                      const SizedBox(height: 6),
                      _buildDropdown(
                        value: _selectedSatuan,
                        hint: 'Pilih satuan',
                        icon: Icons.straighten,
                        items: _listSatuan,
                        onChanged: (v) => setState(() => _selectedSatuan = v),
                        validator: (v) => v == null ? 'Pilih satuan' : null,
                      ),
                    ],
                  );

                  final kategoriWidget = Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildLabel('Kategori *'),
                      const SizedBox(height: 6),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Expanded(child: _buildKategoriDropdown()),
                          const SizedBox(width: 8),
                          SizedBox(
                            height: 50,
                            child: ElevatedButton(
                              onPressed: _isAddingCategory ? null : _showAddCategoryDialog,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFFF3F4F6),
                                foregroundColor: Colors.grey.shade700,
                                elevation: 0,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                padding: const EdgeInsets.symmetric(horizontal: 12),
                              ),
                              child: _isAddingCategory
                                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                                  : const Icon(Icons.add, size: 24),
                            ),
                          ),
                        ],
                      ),
                    ],
                  );

                  // Ambang batas lebar layar sempit. Silakan sesuaikan (mis. 400)
                  // kalau ingin lebih agresif/tidak agresif menumpuk vertikal.
                  const double narrowBreakpoint = 360;
                  final isNarrow = constraints.maxWidth < narrowBreakpoint;

                  if (isNarrow) {
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        satuanWidget,
                        const SizedBox(height: 16),
                        kategoriWidget,
                      ],
                    );
                  }

                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(child: satuanWidget),
                      const SizedBox(width: 12),
                      Expanded(child: kategoriWidget),
                    ],
                  );
                },
              ),
              const SizedBox(height: 16),
              _buildLabel('Deskripsi'),
              const SizedBox(height: 6),
              TextFormField(
                controller: _deskripsiCtrl,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: 'Keterangan tambahan produk (opsional)...',
                  hintStyle: const TextStyle(color: Colors.grey, fontSize: 13),
                  filled: true,
                  fillColor: abukuMuda,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                  contentPadding: const EdgeInsets.all(14),
                ),
              ),
              const SizedBox(height: 16),
              _buildLabel('Gambar Produk'),
              const SizedBox(height: 6),
              GestureDetector(
                onTap: _showImagePickerDialog,
                child: Container(
                  height: 120,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: abukuMuda,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: _imageFile == null
                      ? const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.add_photo_alternate_outlined, color: Colors.grey, size: 40),
                            SizedBox(height: 8),
                            Text('Tap untuk pilih gambar', style: TextStyle(color: Colors.grey)),
                          ],
                        )
                      : ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.file(_imageFile!, fit: BoxFit.cover, width: double.infinity, height: 120),
                        ),
                ),
              ),
              const SizedBox(height: 28),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Batal'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: _isSaving ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: hijauUtama,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: _isSaving
                          ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Simpan'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }
}
