import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile/widgets/tambah_utang_form.dart';
import '../../core/api/api_service.dart';
import '../../utils/format_helper.dart';
import '../cart/cart_service.dart';

import 'struk_model.dart';
import 'struk_bottom_sheet.dart';
import '../../widgets/cari_kode_produk_sheet.dart';

class TransaksiScreen extends StatefulWidget {
  const TransaksiScreen({super.key});

  @override
  State<TransaksiScreen> createState() => _TransaksiScreenState();
}

class _TransaksiScreenState extends State<TransaksiScreen> {
  List<Map<String, dynamic>> _produkList = [];
  List<Map<String, dynamic>> _filteredList = [];
  List<String> _kategoriList = ['Semua'];
  String? _selectedKategori = 'Semua';
  bool isLoading = true;
  bool isCheckingOut = false;
  final TextEditingController _searchController = TextEditingController();

  // Layout constants
  static const double cartPanelWidth = 260;
  static const double cartBarHeight = 92;

  @override
  void initState() {
    super.initState();
    _loadProduk();
    _searchController.addListener(_onSearch);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadProduk() async {
    try {
      final response = await ApiService.get('products');
      final List data = response is List ? response : (response['data'] ?? []);
      final produk = data.map((e) => Map<String, dynamic>.from(e)).toList();

      final Set<String> kategori = {};
      for (var p in produk) {
        final kat = p['category']?['name'] ?? p['kategori'] ?? '';
        if (kat.toString().isNotEmpty) kategori.add(kat.toString());
      }

      setState(() {
        _produkList = produk;
        // ⚠️ FIX: hanya produk yang aktif (is_active) DAN masih ada stok
        // yang ditampilkan di halaman transaksi. Produk yang stoknya habis
        // otomatis is_active=false dari backend, jadi kondisi ini cukup,
        // tapi cek stok tetap dipertahankan sebagai lapisan keamanan kedua.
        _filteredList = produk
            .where((p) => _isActive(p) && _getStok(p) > 0)
            .toList();
        _kategoriList = ['Semua', ...kategori];
        isLoading = false;
      });
    } catch (e) {
      debugPrint('Produk error: $e');
      setState(() => isLoading = false);
    }
  }

  void _onSearch() => _applyFilter();

  void _applyFilter() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredList = _produkList.where((p) {
        final nama = p['name'].toString().toLowerCase();
        final kategori =
            (p['category']?['name'] ?? p['kategori'] ?? '').toString();
        final matchKat =
            _selectedKategori == 'Semua' || kategori == _selectedKategori;
        final matchQ = nama.contains(query);
        // ⚠️ FIX: produk yang tidak aktif atau stoknya habis tetap disaring
        // keluar walau sedang melakukan pencarian atau pindah kategori.
        final aktifDanAdaStok = _isActive(p) && _getStok(p) > 0;
        return matchKat && matchQ && aktifDanAdaStok;
      }).toList();
    });
  }

  void _pilihKategori(String kat) {
    setState(() => _selectedKategori = kat);
    _applyFilter();
  }

  Future<void> _bukaCariKodeProduk() async {
    final kode = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const CariKodeProdukSheet(),
    );
    if (kode == null || kode.isEmpty || !mounted) return;
    await _cariProdukByKode(kode);
  }

  Future<void> _cariProdukByKode(String kode) async {
    try {
      final response =
          await ApiService.get('products/by-barcode/${Uri.encodeComponent(kode)}');
      final produk = Map<String, dynamic>.from(response);
      _tambah(produk);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${produk['name']} ditambahkan ke keranjang')),
        );
      }
    } catch (_) {
      // Fallback: coba cari lewat SKU/nama kalau barcode tidak ketemu persis
      try {
        final response =
            await ApiService.get('products?search=${Uri.encodeComponent(kode)}');
        final List data = response is List ? response : (response['data'] ?? []);
        if (data.isNotEmpty) {
          final produk = Map<String, dynamic>.from(data.first);
          _tambah(produk);
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('${produk['name']} ditambahkan ke keranjang')),
            );
          }
          return;
        }
      } catch (_) {}

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Produk dengan kode "$kode" tidak ditemukan')),
        );
      }
    }
  }

  int _getQty(dynamic id) {
    final idx = CartService.cart.indexWhere((e) => e['id'] == id);
    return idx != -1 ? CartService.cart[idx]['qty'] as int : 0;
  }

  // Helper terpusat untuk mengambil nilai stok dari sebuah map produk/item.
  int _getStok(Map<String, dynamic> produk) {
    final stok = produk['stock'] ?? produk['stok'] ?? 0;
    if (stok is int) return stok;
    return int.tryParse(stok.toString()) ?? 0;
  }

  // ⚠️ FIX: helper untuk cek status aktif produk. Backend sekarang otomatis
  // men-set is_active = false saat stok produk 0 (lihat Product::boot() di
  // Laravel), jadi field ini adalah sumber kebenaran utama untuk menentukan
  // apakah sebuah produk boleh tampil di halaman transaksi. Default true
  // kalau field tidak ada, supaya data lama yang belum punya kolom ini tidak
  // ikut hilang dari daftar.
  bool _isActive(Map<String, dynamic> produk) {
    final aktif = produk['is_active'] ?? produk['aktif'];
    if (aktif == null) return true;
    if (aktif is bool) return aktif;
    if (aktif is int) return aktif != 0;
    return aktif.toString().toLowerCase() == 'true' || aktif.toString() == '1';
  }

  // ⚠️ FIX: _tambah sekarang menjadi satu-satunya gerbang penambahan qty.
  // Produk dengan stok habis (stok <= 0) tidak bisa ditambahkan sama sekali,
  // dan qty di keranjang tidak boleh melebihi stok yang tersedia.
  void _tambah(Map<String, dynamic> produk) {
    final stok = _getStok(produk);

    // Stok habis -> nonaktif total, tidak melakukan apa-apa.
    if (stok <= 0) return;

    final qtySekarang = _getQty(produk['id']);
    if (qtySekarang >= stok) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Qty sudah mencapai batas stok tersedia')),
      );
      return;
    }

    setState(() => CartService.addToCart(produk));
  }

  void _kurang(dynamic id) {
    setState(() {
      final idx = CartService.cart.indexWhere((e) => e['id'] == id);
      if (idx != -1) {
        CartService.cart[idx]['qty']--;
        if (CartService.cart[idx]['qty'] <= 0) {
          CartService.cart.removeAt(idx);
        }
      }
    });
  }

  // ⚠️ FIX: memungkinkan qty diketik langsung lewat keyboard (bukan cuma
  // lewat tombol -/+). Nilai otomatis dibatasi ke rentang [0, stok]; qty 0
  // akan menghapus produk dari keranjang.
  void _setQty(Map<String, dynamic> produk, int newQty) {
    final id = produk['id'];
    final stok = _getStok(produk);
    final idx = CartService.cart.indexWhere((e) => e['id'] == id);

    if (newQty <= 0) {
      if (idx != -1) setState(() => CartService.cart.removeAt(idx));
      return;
    }

    final melebihiStok = stok > 0 && newQty > stok;
    final clamped = stok > 0 ? newQty.clamp(1, stok) : newQty;

    setState(() {
      if (idx != -1) {
        CartService.cart[idx]['qty'] = clamped;
      } else {
        CartService.addToCart(produk);
        final newIdx = CartService.cart.indexWhere((e) => e['id'] == id);
        if (newIdx != -1) CartService.cart[newIdx]['qty'] = clamped;
      }
    });

    if (melebihiStok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Qty melebihi stok, disesuaikan otomatis')),
      );
    }
  }

  Future<void> _checkout() async {
    if (isCheckingOut || CartService.cart.isEmpty) return;
    setState(() => isCheckingOut = true);
    await _prosesCheckout();
    if (mounted) setState(() => isCheckingOut = false);
  }

  Future<void> _prosesCheckout() async {
    try {
      final lanjut = await showModalBottomSheet<bool>(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (ctx) => _KeranjangDetailSheet(
          items: CartService.cart,
          total: CartService.totalHarga,
          onLanjutBayar: () => Navigator.pop(ctx, true),
        ),
      );

      if (mounted) setState(() {});
      if (lanjut != true || !mounted) return;

      final hasil = await showModalBottomSheet<Map<String, dynamic>>(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (ctx) => _PaymentMethodSheet(total: CartService.totalHarga),
      );

      if (hasil == null || !mounted) return;

      final paymentType = hasil['payment_type'] as String;
      final nominalBayar = hasil['bayar'] as int?;

      final itemsSnapshot =
          CartService.cart.map((e) => Map<String, dynamic>.from(e)).toList();
      final totalSnapshot = CartService.totalHarga;

      if (paymentType == 'debt') {
        Map<String, dynamic>? utangPayload;

        await showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (_) => TambahUtangForm(
            initialCartItems: itemsSnapshot,
            onSimpan: (payload) async {
              utangPayload = payload;
            },
          ),
        );

        if (utangPayload == null || !mounted) return;

        // ⚠️ FIX: due_date dari TambahUtangForm sekarang ikut diteruskan.
        // Sebelumnya hanya customer_id & notes yang dikirim, sehingga
        // jatuh tempo yang dipilih kasir di form selalu hilang dan
        // tersimpan null di database.
        final response = await CartService.checkout(
          paymentType: 'debt',
          customerId: utangPayload!['customer_id'],
          notes: utangPayload!['notes'],
          dueDate: utangPayload!['due_date'],
        );

        if (!mounted) return;
        setState(() {});

        final nomorStruk = response['invoice_number'] ??
            'TRX-${DateTime.now().millisecondsSinceEpoch}';

        final struk = StrukModel(
          nomorStruk: nomorStruk.toString(),
          waktu: DateTime.now(),
          items: itemsSnapshot,
          totalHarga: totalSnapshot,
          paymentType: 'debt',
          bayar: null,
        );

        if (!mounted) return;
        await showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (_) => StrukBottomSheet(struk: struk),
        );
      } else {
        final response = await CartService.checkout(
          paymentType: paymentType,
        );

        if (!mounted) return;
        setState(() {});

        final nomorStruk = response['invoice_number'] ??
            response['no_struk'] ??
            'TRX-${DateTime.now().millisecondsSinceEpoch}';

        final struk = StrukModel(
          nomorStruk: nomorStruk.toString(),
          waktu: DateTime.now(),
          items: itemsSnapshot,
          totalHarga: totalSnapshot,
          paymentType: paymentType,
          bayar: nominalBayar,
        );

        if (!mounted) return;
        await showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (_) => StrukBottomSheet(struk: struk),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal checkout: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final shortestSide = MediaQuery.of(context).size.shortestSide;
    final isTablet = shortestSide >= 600;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFFF8FAFC),
      body: SafeArea(
        bottom: true,
        child: Column(
          children: [
            // Search + Filter
            Container(
              color: Colors.white,
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFFF3F4F6),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: TextField(
                            controller: _searchController,
                            decoration: const InputDecoration(
                              hintText: 'Cari Sembako...',
                              hintStyle: TextStyle(color: Color(0xFF9CA3AF)),
                              prefixIcon:
                                  Icon(Icons.search, color: Color(0xFF9CA3AF)),
                              border: InputBorder.none,
                              contentPadding:
                                  EdgeInsets.symmetric(vertical: 12),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      GestureDetector(
                        onTap: _bukaCariKodeProduk,
                        child: Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: const Color(0xFF059669),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child:
                              const Icon(Icons.qr_code_scanner, color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 36,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _kategoriList.length,
                      separatorBuilder: (_, __) => const SizedBox(width: 8),
                      itemBuilder: (context, i) {
                        final kat = _kategoriList[i];
                        final selected = kat == _selectedKategori;
                        return GestureDetector(
                          onTap: () => _pilihKategori(kat),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 8),
                            decoration: BoxDecoration(
                              color: selected ? const Color(0xFF059669) : Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(
                                color: selected
                                    ? const Color(0xFF059669)
                                    : const Color(0xFFE5E7EB),
                              ),
                            ),
                            child: Text(
                              kat,
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: selected ? Colors.white : const Color(0xFF374151),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),

            // Content area
            Expanded(
              child: isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : isTablet
                      ? Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Produk grid (kiri) — 3 kolom di tablet
                            Expanded(
                              child: Padding(
                                padding:
                                    const EdgeInsets.fromLTRB(12, 12, 12, 12),
                                child: _buildGrid(crossCount: 3),
                              ),
                            ),

                            // Panel Keranjang (kanan)
                            Container(
                              width: cartPanelWidth,
                              margin:
                                  const EdgeInsets.only(right: 12, top: 12, bottom: 12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(
                                    color: const Color.fromRGBO(0, 0, 0, 0.04),
                                    blurRadius: 8,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: _CartSidePanel(
                                onCheckout: _checkout,
                                isCheckingOut: isCheckingOut,
                                onTambah: _tambah,
                                onKurang: _kurang,
                                onSetQty: (id, newQty, stok) {
                                  final idx = CartService.cart
                                      .indexWhere((e) => e['id'] == id);
                                  if (idx == -1) return;
                                  _setQty(
                                    Map<String, dynamic>.from(
                                        CartService.cart[idx]),
                                    newQty,
                                  );
                                },
                              ),
                            ),
                          ],
                        )
                      : // Mobile: 2 kolom + bottom cart bar
                      Column(
                          children: [
                            Expanded(child: _buildGrid(crossCount: 2)),
                            if (CartService.cart.isNotEmpty) _buildCartBar(),
                          ],
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGrid({required int crossCount}) {
    if (_filteredList.isEmpty) {
      return const Center(child: Text('Produk tidak ditemukan'));
    }

    final mq = MediaQuery.of(context);
    final screenW = mq.size.width;
    final isTablet = mq.size.shortestSide >= 600;
    final isPortrait = mq.orientation == Orientation.portrait;

    final horizontalPadding = 12.0 * 2;
    final spacing = 8.0 * (crossCount - 1);
    final availableWidth = isTablet
        ? (screenW - cartPanelWidth - horizontalPadding - 12)
        : (screenW - horizontalPadding);

    final cardWidth = (availableWidth - spacing) / crossCount;

    // Tinggi card tetap — cukup untuk semua konten (termasuk stepper qty
    // yang bisa diketik) tanpa overflow.
    final cardHeight = isTablet
        ? (isPortrait ? 246.0 : 226.0)
        : (isPortrait ? 226.0 : 211.0);

    final childAspectRatio = cardWidth / cardHeight;

    // Grid sekarang jadi sibling dari cart bar (bukan ditumpuk via Stack),
    // jadi cukup padding standar — tidak perlu lagi menghitung tinggi
    // cart bar secara manual.
    const bottomPadding = 12.0;

    // Kunci text scaling di area grid supaya pengaturan "ukuran font" HP
    // pengguna tidak membuat konten kartu overflow dan terlihat saling
    // tumpuk tindih.
    return MediaQuery(
      data: mq.copyWith(textScaler: const TextScaler.linear(1.0)),
      child: GridView.builder(
        padding: const EdgeInsets.fromLTRB(12, 12, 12, bottomPadding),
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: crossCount,
          crossAxisSpacing: 8,
          mainAxisSpacing: 8,
          childAspectRatio: childAspectRatio,
        ),
        itemCount: _filteredList.length,
        itemBuilder: (context, index) {
          final produk = _filteredList[index];
          final qty = _getQty(produk['id']);
          final stok = _getStok(produk);
          final harga = double.parse(produk['selling_price'].toString());
          final kat = (produk['category']?['name'] ?? produk['kategori'] ?? '')
              .toString()
              .toUpperCase();
          final menipis = stok > 0 && stok < 10;

          // Catatan: produk dengan stok habis (stok <= 0) sudah disaring keluar
          // di _applyFilter()/_loadProduk(), jadi tidak akan pernah sampai ke
          // sini. GestureDetector di bawah tetap aman karena _tambah() masih
          // punya guard stok sebagai lapisan pertahanan kedua.
          return _ProdukCard(
            nama: produk['name'] ?? '-',
            harga: harga,
            stok: stok,
            kategori: kat,
            menipis: menipis,
            imageUrl: produk['image_url'] ?? produk['foto'] ?? '',
            qty: qty,
            onTambah: () => _tambah(produk),
            onKurang: () => _kurang(produk['id']),
            onSetQty: (val) => _setQty(produk, val),
          );
        },
      ),
    );
  }

  Widget _buildCartBar() {
    final lastItem = CartService.cart.last;
    final namaItem = lastItem['name'] ?? '-';
    final hargaItem = double.parse(lastItem['selling_price'].toString());
    final qtyItem = lastItem['qty'] as int;
    final stokItem = _getStok(lastItem);
    final habisItem = stokItem <= 0 || qtyItem >= stokItem;

    return SafeArea(
      top: false,
      child: Container(
        height: cartBarHeight,
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: Color(0xFFE5E7EB))),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: const Color(0xFFD1FAE5),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.shopping_cart_outlined,
                  color: Color(0xFF059669), size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    namaItem,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: Color(0xFF111827),
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    '${formatRupiah(hargaItem)} × $qtyItem Item',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF6B7280)),
                  ),
                ],
              ),
            ),
            Row(
              children: [
                GestureDetector(
                  onTap: () => _kurang(lastItem['id']),
                  child: Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFFE5E7EB)),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.remove, size: 16),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: _QtyEditor(
                    qty: qtyItem,
                    maxQty: stokItem > 0 ? stokItem : null,
                    width: 44,
                    height: 30,
                    fontSize: 14,
                    onChanged: (val) => _setQty(lastItem, val),
                  ),
                ),
                // ⚠️ FIX: tombol tambah di cart bar juga dinonaktifkan bila
                // qty sudah mencapai batas stok, supaya konsisten dengan
                // aturan di _tambah().
                GestureDetector(
                  onTap: habisItem ? null : () => _tambah(lastItem),
                  child: Container(
                    width: 28,
                    height: 28,
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFFE5E7EB)),
                      borderRadius: BorderRadius.circular(8),
                      color: habisItem ? const Color(0xFFF3F4F6) : null,
                    ),
                    child: Icon(
                      Icons.add,
                      size: 16,
                      color: habisItem ? const Color(0xFFD1D5DB) : null,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 12),
            ElevatedButton(
              onPressed: isCheckingOut ? null : _checkout,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF059669),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: isCheckingOut
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Bayar', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }
}

// -----------------------------
// Cart side panel widget
// -----------------------------
class _CartSidePanel extends StatefulWidget {
  final VoidCallback onCheckout;
  final bool isCheckingOut;
  final void Function(Map<String, dynamic>) onTambah;
  final void Function(dynamic) onKurang;
  final void Function(dynamic id, int newQty, int stok) onSetQty;

  const _CartSidePanel({
    required this.onCheckout,
    required this.isCheckingOut,
    required this.onTambah,
    required this.onKurang,
    required this.onSetQty,
  });

  @override
  State<_CartSidePanel> createState() => _CartSidePanelState();
}

class _CartSidePanelState extends State<_CartSidePanel> {
  int _getStok(Map<String, dynamic> item) {
    final stok = item['stock'] ?? item['stok'] ?? 0;
    if (stok is int) return stok;
    return int.tryParse(stok.toString()) ?? 0;
  }

  @override
  Widget build(BuildContext context) {
    final items = CartService.cart;
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          Row(
            children: const [
              Icon(Icons.shopping_cart_outlined, color: Color(0xFF059669)),
              SizedBox(width: 8),
              Text('Keranjang', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              Spacer(),
            ],
          ),
          const SizedBox(height: 10),
          const Divider(color: Color(0xFFE5E7EB)),
          Expanded(
            child: items.isEmpty
                ? const Center(child: Text('Keranjang kosong'))
                : ListView.separated(
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const Divider(color: Color(0xFFE5E7EB)),
                    itemBuilder: (_, i) {
                      final item = items[i];
                      final nama = item['name'] ?? '-';
                      final qty = item['qty'] as int;
                      final harga = double.parse(item['selling_price'].toString());
                      final subtotal = harga * qty;
                      final stok = _getStok(item);
                      final habisTambah = stok > 0 && qty >= stok;
                      // ⚠️ FIX: layout item keranjang diubah jadi 2 baris —
                      // baris 1 nama produk (agar tidak terpotong/turun
                      // baris berkali-kali seperti sebelumnya), baris 2
                      // stepper qty [-  angka  +] di kiri sejajar dengan
                      // subtotal di kanan.
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              nama,
                              style: const TextStyle(fontWeight: FontWeight.w600),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    GestureDetector(
                                      onTap: () => widget.onKurang(item['id']),
                                      child: Container(
                                        width: 30, height: 30,
                                        decoration: BoxDecoration(
                                          color: qty == 1 ? const Color(0xFFFEE2E2) : const Color(0xFFF3F4F6),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(qty == 1 ? Icons.delete_outline : Icons.remove, size: 16, color: qty == 1 ? const Color(0xFFDC2626) : const Color(0xFF374151)),
                                      ),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 4),
                                      child: _QtyEditor(
                                        qty: qty,
                                        maxQty: stok > 0 ? stok : null,
                                        onChanged: (val) =>
                                            widget.onSetQty(item['id'], val, stok),
                                      ),
                                    ),
                                    // ⚠️ FIX: nonaktifkan tombol "+" saat qty sudah
                                    // mencapai batas stok yang tersedia.
                                    GestureDetector(
                                      onTap: habisTambah ? null : () => widget.onTambah(item),
                                      child: Container(
                                        width: 30, height: 30,
                                        decoration: BoxDecoration(
                                          color: habisTambah ? const Color(0xFFF3F4F6) : const Color(0xFFD1FAE5),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(Icons.add, size: 16, color: habisTambah ? const Color(0xFFD1D5DB) : const Color(0xFF059669)),
                                      ),
                                    ),
                                  ],
                                ),
                                Text(
                                  formatRupiah(subtotal),
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ],
                        ),
                      );
                    },
                  ),
          ),
          const Divider(color: Color(0xFFE5E7EB)),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Total', style: TextStyle(fontSize: 12, color: Color(0xFF6B7280))),
              Text(formatRupiah(CartService.totalHarga.toDouble()), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF059669))),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: widget.isCheckingOut ? null : widget.onCheckout,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF059669),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: widget.isCheckingOut
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Bayar', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }
}

// KERANJANG DETAIL SHEET (dipanggil di proses checkout)
class _KeranjangDetailSheet extends StatefulWidget {
  final List<Map<String, dynamic>> items;
  final int total;
  final VoidCallback onLanjutBayar;

  const _KeranjangDetailSheet({
    required this.items,
    required this.total,
    required this.onLanjutBayar,
  });

  @override
  State<_KeranjangDetailSheet> createState() => _KeranjangDetailSheetState();
}

class _KeranjangDetailSheetState extends State<_KeranjangDetailSheet> {
  List<Map<String, dynamic>> get _items => CartService.cart;
  int get _total => CartService.totalHarga;

  int _getStok(Map<String, dynamic> item) {
    final stok = item['stock'] ?? item['stok'] ?? 0;
    if (stok is int) return stok;
    return int.tryParse(stok.toString()) ?? 0;
  }

  // ⚠️ FIX: sheet ini sebelumnya memanggil CartService.addToCart() langsung,
  // jadi lolos dari pengecekan stok yang ada di _tambah() milik
  // TransaksiScreen. Sekarang qty juga tidak boleh melebihi stok di sini.
  void _tambah(Map<String, dynamic> item) {
    final stok = _getStok(item);
    final qtySekarang = item['qty'] as int;
    if (stok > 0 && qtySekarang >= stok) return;
    setState(() => CartService.addToCart(item));
  }

  void _kurang(dynamic id) {
    setState(() {
      final idx = CartService.cart.indexWhere((e) => e['id'] == id);
      if (idx != -1) {
        CartService.cart[idx]['qty']--;
        if (CartService.cart[idx]['qty'] <= 0) {
          CartService.cart.removeAt(idx);
        }
      }
    });

    if (CartService.cart.isEmpty && mounted) {
      Navigator.pop(context);
    }
  }

  // ⚠️ FIX: qty di sheet ini sekarang juga bisa diketik langsung lewat
  // keyboard, dengan batas stok yang sama seperti _tambah()/_kurang().
  void _setQty(dynamic id, int newQty, int stok) {
    final idx = CartService.cart.indexWhere((e) => e['id'] == id);
    if (idx == -1) return;

    if (newQty <= 0) {
      setState(() => CartService.cart.removeAt(idx));
      if (CartService.cart.isEmpty && mounted) Navigator.pop(context);
      return;
    }

    final clamped = stok > 0 ? newQty.clamp(1, stok) : newQty;
    setState(() => CartService.cart[idx]['qty'] = clamped);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: const Color(0xFFE5E7EB),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              const Icon(Icons.shopping_cart_outlined,
                  color: Color(0xFF059669), size: 20),
              const SizedBox(width: 8),
              const Text(
                'Detail Pesanan',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF111827),
                ),
              ),
              const Spacer(),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${_items.fold(0, (s, e) => s + (e['qty'] as int))} item',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF059669),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Divider(color: Color(0xFFE5E7EB)),
          ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.38,
            ),
            child: ListView.separated(
              shrinkWrap: true,
              itemCount: _items.length,
              separatorBuilder: (_, __) =>
                  const Divider(color: Color(0xFFE5E7EB), height: 1),
              itemBuilder: (_, i) {
                final item = _items[i];
                final nama = item['name'] ?? '-';
                final qty = item['qty'] as int;
                final harga = double.parse(item['selling_price'].toString());
                final subtotal = harga * qty;
                final stok = _getStok(item);
                final habisTambah = stok > 0 && qty >= stok;
                // ⚠️ FIX: sama seperti di _CartSidePanel — nama produk
                // dipisah jadi baris sendiri di atas, lalu stepper qty
                // sejajar dengan subtotal di baris bawah.
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nama,
                        style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF111827)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              GestureDetector(
                                onTap: () => _kurang(item['id']),
                                child: Container(
                                  width: 30,
                                  height: 30,
                                  decoration: BoxDecoration(
                                    color: qty == 1
                                        ? const Color(0xFFFEE2E2)
                                        : const Color(0xFFF3F4F6),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Icon(
                                    qty == 1 ? Icons.delete_outline : Icons.remove,
                                    size: 16,
                                    color: qty == 1
                                        ? const Color(0xFFDC2626)
                                        : const Color(0xFF374151),
                                  ),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.symmetric(horizontal: 4),
                                child: _QtyEditor(
                                  qty: qty,
                                  maxQty: stok > 0 ? stok : null,
                                  onChanged: (val) =>
                                      _setQty(item['id'], val, stok),
                                ),
                              ),
                              GestureDetector(
                                onTap: habisTambah ? null : () => _tambah(item),
                                child: Container(
                                  width: 30,
                                  height: 30,
                                  decoration: BoxDecoration(
                                    color: habisTambah
                                        ? const Color(0xFFF3F4F6)
                                        : const Color(0xFFD1FAE5),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Icon(Icons.add,
                                      size: 16,
                                      color: habisTambah
                                          ? const Color(0xFFD1D5DB)
                                          : const Color(0xFF059669)),
                                ),
                              ),
                            ],
                          ),
                          Text(
                            formatRupiah(subtotal),
                            style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF111827)),
                          ),
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
          const Divider(color: Color(0xFFE5E7EB)),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${_items.length} produk',
                style: const TextStyle(fontSize: 12, color: Color(0xFF6B7280)),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text('Total',
                      style: TextStyle(fontSize: 11, color: Color(0xFF6B7280))),
                  Text(
                    formatRupiah(_total.toDouble()),
                    style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF059669)),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: widget.onLanjutBayar,
              icon: const Icon(Icons.payment, size: 18),
              label: const Text(
                'Lanjut ke Pembayaran',
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF059669),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────
// PAYMENT METHOD SHEET
// ─────────────────────────────────────────────
class _PaymentMethodSheet extends StatefulWidget {
  final int total;
  const _PaymentMethodSheet({required this.total});

  @override
  State<_PaymentMethodSheet> createState() => _PaymentMethodSheetState();
}

class _PaymentMethodSheetState extends State<_PaymentMethodSheet> {
  final TextEditingController _nominalController = TextEditingController();
  int _nominalDipilih = 0;
  String? _metodNonTunai;

  final List<int> _uangCepat = [2000, 5000, 10000, 20000, 50000, 100000];

  int get _nominal => _metodNonTunai != null
      ? widget.total
      : (_nominalDipilih > 0
          ? _nominalDipilih
          : int.tryParse(_nominalController.text
                  .replaceAll('.', '')
                  .replaceAll(',', '')) ??
              0);

  int get _kembalian => _nominal - widget.total;

  bool get _valid {
    if (_metodNonTunai != null) return true;
    return _nominal >= widget.total;
  }

  void _pilihUangCepat(int nilai) {
    setState(() {
      _nominalDipilih = nilai;
      _metodNonTunai = null;
      _nominalController.text = nilai.toString();
    });
  }

  void _uangPas() {
    setState(() {
      _nominalDipilih = widget.total;
      _metodNonTunai = null;
      _nominalController.text = widget.total.toString();
    });
  }

  void _pilihNonTunai(String metode) {
    setState(() {
      _metodNonTunai = metode;
      _nominalDipilih = 0;
      _nominalController.clear();
    });
  }

  void _konfirmasi() {
    if (!_valid) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal kurang dari total tagihan!')),
      );
      return;
    }
    Navigator.pop(context, {
      'payment_type': _metodNonTunai ?? 'cash',
      'bayar': _metodNonTunai != null ? widget.total : _nominal,
    });
  }

  String _formatRibu(int nilai) {
    if (nilai >= 1000000) {
      return '${(nilai / 1000000).toStringAsFixed(0)}jt';
    }
    if (nilai >= 1000) return '${(nilai / 1000).toStringAsFixed(0)}k';
    return '$nilai';
  }

  @override
  void dispose() {
    _nominalController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.fromLTRB(
          20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
      child: SingleChildScrollView(
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
            const SizedBox(height: 20),
            const Text(
              'Selesaikan Pembayaran',
              style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF059669)),
            ),
            const SizedBox(height: 6),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Tagihan Total',
                  style: TextStyle(fontSize: 13, color: Color(0xFF6B7280)),
                ),
                Text(
                  formatRupiah(widget.total.toDouble()),
                  style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF111827)),
                ),
              ],
            ),
            if (_metodNonTunai == null && _kembalian > 0) ...[
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Kembalian',
                    style: TextStyle(fontSize: 13, color: Color(0xFF6B7280)),
                  ),
                  Text(
                    formatRupiah(_kembalian.toDouble()),
                    style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF059669)),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 20),
            const Divider(color: Color(0xFFE5E7EB)),
            const SizedBox(height: 16),
            const Text(
              'UANG TUNAI CEPAT',
              style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF9CA3AF),
                  letterSpacing: 1),
            ),
            const SizedBox(height: 10),
            GridView.count(
              crossAxisCount: 3,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
              childAspectRatio: 2.4,
              children: _uangCepat.map((nilai) {
                final selected =
                    _nominalDipilih == nilai && _metodNonTunai == null;
                return GestureDetector(
                  onTap: () => _pilihUangCepat(nilai),
                  child: Container(
                    decoration: BoxDecoration(
                      color: selected
                          ? const Color(0xFF059669)
                          : const Color(0xFFF3F4F6),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Center(
                      child: Text(
                        _formatRibu(nilai),
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: selected
                                ? Colors.white
                                : const Color(0xFF059669)),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 10),
            GestureDetector(
              onTap: _uangPas,
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  color:
                      _nominalDipilih == widget.total && _metodNonTunai == null
                          ? const Color(0xFF059669)
                          : const Color(0xFFD1FAE5),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.wallet_outlined,
                      size: 18,
                      color: _nominalDipilih == widget.total &&
                              _metodNonTunai == null
                          ? Colors.white
                          : const Color(0xFF059669),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      'Uang Pas',
                      style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          color: _nominalDipilih == widget.total &&
                                  _metodNonTunai == null
                              ? Colors.white
                              : const Color(0xFF059669)),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'NOMINAL',
              style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF9CA3AF),
                  letterSpacing: 1),
            ),
            const SizedBox(height: 10),
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF3F4F6),
                borderRadius: BorderRadius.circular(12),
              ),
              child: TextField(
                controller: _nominalController,
                keyboardType: TextInputType.number,
                onChanged: (val) => setState(() {
                  final angka = int.tryParse(
                          val.replaceAll('.', '').replaceAll(',', '')) ??
                      0;
                  _nominalDipilih = _uangCepat.contains(angka) ? angka : 0;
                  _metodNonTunai = null;
                }),
                decoration: const InputDecoration(
                  prefixText: 'Rp  ',
                  prefixStyle: TextStyle(
                      fontSize: 15,
                      color: Color(0xFF111827),
                      fontWeight: FontWeight.w500),
                  hintText: '0',
                  hintStyle: TextStyle(color: Color(0xFF9CA3AF)),
                  border: InputBorder.none,
                  contentPadding:
                      EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'METODE NON-TUNAI',
              style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF9CA3AF),
                  letterSpacing: 1),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                _NonTunaiCard(
                  label: 'QRIS / Debit',
                  icon: Icons.qr_code_2,
                  selected: _metodNonTunai == 'qris',
                  warna: const Color(0xFF059669),
                  onTap: () => _pilihNonTunai('qris'),
                ),
                const SizedBox(width: 12),
                _NonTunaiCard(
                  label: 'Utang',
                  icon: Icons.account_balance_wallet_outlined,
                  selected: _metodNonTunai == 'debt',
                  warna: const Color(0xFFDB2777),
                  onTap: () => _pilihNonTunai('debt'),
                ),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _valid ? _konfirmasi : null,
                icon: const Icon(Icons.check_circle_outline, size: 20),
                label: const Text(
                  'Konfirmasi Bayar',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  disabledBackgroundColor: const Color(0xFFD1FAE5),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// KARTU NON-TUNAI
// ─────────────────────────────────────────────
class _NonTunaiCard extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final Color warna;
  final VoidCallback onTap;

  const _NonTunaiCard({
    required this.label,
    required this.icon,
    required this.selected,
    required this.warna,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: selected ? warna.withOpacity(0.1) : const Color(0xFFF9FAFB),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: selected ? warna : const Color(0xFFE5E7EB),
              width: selected ? 2 : 1,
            ),
          ),
          child: Column(
            children: [
              Icon(icon, color: warna, size: 28),
              const SizedBox(height: 6),
              Text(
                label,
                style: TextStyle(
                    fontSize: 13, fontWeight: FontWeight.w600, color: warna),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// QTY EDITOR — field angka yang bisa diketik lewat keyboard,
// dipakai bersamaan dengan tombol -/+ di semua tempat.
// ─────────────────────────────────────────────
class _QtyEditor extends StatefulWidget {
  final int qty;
  final int? maxQty; // null = tidak dibatasi
  final ValueChanged<int> onChanged;
  final double width;
  final double height;
  final double fontSize;

  const _QtyEditor({
    required this.qty,
    required this.onChanged,
    this.maxQty,
    this.width = 40,
    this.height = 28,
    this.fontSize = 13,
  });

  @override
  State<_QtyEditor> createState() => _QtyEditorState();
}

class _QtyEditorState extends State<_QtyEditor> {
  late final TextEditingController _controller =
      TextEditingController(text: widget.qty.toString());
  late final FocusNode _focusNode = FocusNode()
    ..addListener(_onFocusChange);

  @override
  void didUpdateWidget(covariant _QtyEditor oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Sinkronkan teks bila qty berubah dari luar (mis. lewat tombol -/+)
    // selama field ini sedang tidak difokus/diketik oleh pengguna.
    if (!_focusNode.hasFocus && widget.qty.toString() != _controller.text) {
      _controller.text = widget.qty.toString();
    }
  }

  void _onFocusChange() {
    if (!_focusNode.hasFocus) _commit();
  }

  void _commit() {
    final parsed = int.tryParse(_controller.text.trim());
    int value = parsed ?? widget.qty;
    if (value < 0) value = 0;
    if (widget.maxQty != null && value > widget.maxQty!) {
      value = widget.maxQty!;
    }
    _controller.text = value.toString();
    if (value != widget.qty) widget.onChanged(value);
  }

  @override
  void dispose() {
    _focusNode.removeListener(_onFocusChange);
    _focusNode.dispose();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: widget.width,
      height: widget.height,
      child: TextField(
        controller: _controller,
        focusNode: _focusNode,
        textAlign: TextAlign.center,
        keyboardType: TextInputType.number,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        textInputAction: TextInputAction.done,
        style: TextStyle(
          fontSize: widget.fontSize,
          fontWeight: FontWeight.bold,
          color: const Color(0xFF111827),
        ),
        decoration: const InputDecoration(
          isDense: true,
          contentPadding: EdgeInsets.symmetric(vertical: 4),
          filled: true,
          fillColor: Color(0xFFF3F4F6),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(8)),
            borderSide: BorderSide.none,
          ),
        ),
        onSubmitted: (_) => _commit(),
      ),
    );
  }
}

// -----------------------------
// ProdukCard widget (responsif)
// -----------------------------
class _ProdukCard extends StatelessWidget {
  final String nama;
  final double harga;
  final int stok;
  final String kategori;
  final bool menipis;
  final String imageUrl;
  final int qty;
  final VoidCallback onTambah;
  final VoidCallback onKurang;
  final ValueChanged<int> onSetQty;

  const _ProdukCard({
    required this.nama,
    required this.harga,
    required this.stok,
    required this.kategori,
    required this.menipis,
    required this.imageUrl,
    required this.qty,
    required this.onTambah,
    required this.onKurang,
    required this.onSetQty,
  });

  @override
  Widget build(BuildContext context) {
    final habis = stok <= 0;
    final habisTambah = habis || qty >= stok;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Gambar
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
            child: SizedBox(
              height: 90, // ← sesuaikan jika perlu
              width: double.infinity,
              child: imageUrl.isNotEmpty
                  ? Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _placeholder(),
                    )
                  : _placeholder(),
            ),
          ),
          // Area teks & tombol
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(6, 6, 6, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Kategori + badge stok
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Flexible(
                        child: Text(
                          kategori,
                          style: const TextStyle(
                              fontSize: 8,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF059669)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                        decoration: BoxDecoration(
                          color: stok <= 0
                              ? const Color(0xFFFEE2E2)
                              : menipis
                                  ? const Color(0xFFFEF3C7)
                                  : const Color(0xFFD1FAE5),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          stok <= 0
                              ? 'Habis'
                              : menipis
                                  ? 'Menipis'
                                  : 'Stok: $stok',
                          style: TextStyle(
                              fontSize: 7,
                              fontWeight: FontWeight.w600,
                              color: stok <= 0
                                  ? const Color(0xFFDC2626)
                                  : menipis
                                      ? const Color(0xFFD97706)
                                      : const Color(0xFF059669)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  // Nama
                  Text(
                    nama,
                    style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF111827)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 3),
                  // Harga
                  Text(
                    formatRupiah(harga),
                    style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF059669)),
                  ),
                  const Spacer(),
                  // Tombol
                  habis
                      ? Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEE2E2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Center(
                            child: Text('Habis',
                                style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFFDC2626))),
                          ),
                        )
                      : qty == 0
                          ? GestureDetector(
                              onTap: onTambah,
                              child: Container(
                                width: double.infinity,
                                padding: const EdgeInsets.symmetric(vertical: 6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF3F4F6),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.add, size: 12, color: Color(0xFF374151)),
                                    SizedBox(width: 4),
                                    Text('Tambah',
                                        style: TextStyle(
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                            color: Color(0xFF374151))),
                                  ],
                                ),
                              ),
                            )
                          // ⚠️ FIX: qty>0 sekarang menampilkan stepper penuh
                          // [-] [angka, bisa diketik lewat keyboard] [+],
                          // bukan cuma tombol "Terpilih" yang hanya bisa
                          // ditekan. Tombol "+" tetap dinonaktifkan jika qty
                          // sudah menyentuh batas stok.
                          : Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                GestureDetector(
                                  onTap: onKurang,
                                  child: Container(
                                    width: 24,
                                    height: 24,
                                    decoration: BoxDecoration(
                                      color: qty == 1
                                          ? const Color(0xFFFEE2E2)
                                          : const Color(0xFFF3F4F6),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Icon(
                                      qty == 1
                                          ? Icons.delete_outline
                                          : Icons.remove,
                                      size: 14,
                                      color: qty == 1
                                          ? const Color(0xFFDC2626)
                                          : const Color(0xFF374151),
                                    ),
                                  ),
                                ),
                                _QtyEditor(
                                  qty: qty,
                                  maxQty: stok > 0 ? stok : null,
                                  width: 34,
                                  height: 24,
                                  fontSize: 11,
                                  onChanged: onSetQty,
                                ),
                                GestureDetector(
                                  onTap: habisTambah ? null : onTambah,
                                  child: Container(
                                    width: 24,
                                    height: 24,
                                    decoration: BoxDecoration(
                                      color: habisTambah
                                          ? const Color(0xFFE5E7EB)
                                          : const Color(0xFF059669),
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Icon(
                                      Icons.add,
                                      size: 14,
                                      color: habisTambah
                                          ? const Color(0xFF9CA3AF)
                                          : Colors.white,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _placeholder() {
    return Container(
      color: const Color(0xFFF3F4F6),
      child: const Center(
        child: Icon(Icons.image_outlined, color: Color(0xFF9CA3AF), size: 30),
      ),
    );
  }
}