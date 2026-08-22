// lib/models/produk.dart
class Produk {
  final String? id;
  final String sku;
  final String nama;
  final String kategori;
  final String categoryId;
  final String satuan;
  final int stok;
  final String? imageUrl;
  final int? hargaJual;
  final int? hargaBeli;
  final String? deskripsi;
  final String? barcode;
  int jumlahDitambah;

  Produk({
    this.id,
    required this.sku,
    required this.nama,
    required this.kategori,
    required this.categoryId,
    required this.satuan,
    required this.stok,
    this.imageUrl,
    this.hargaJual,
    this.hargaBeli,
    this.deskripsi,
    this.barcode,
    this.jumlahDitambah = 0,
  });

  factory Produk.fromJson(Map<String, dynamic> json) {
  final idVal = json['id'];
  final stockVal = json['stock'] ?? json['stok'] ?? 0;
  final selling = json['selling_price'] ?? json['sellingPrice'];
  final purchase = json['purchase_price'] ?? json['purchasePrice'] ?? json['purchasePr'];

  String catName = '';
  String catId = '';
  if (json['category'] is Map) {
    catName = (json['category']['name'] ?? '').toString();
    catId = (json['category']['id'] ?? '').toString();
  } else {
    catName = (json['category_name'] ?? json['kategori'] ?? '').toString();
    catId = (json['category_id'] ?? '').toString();
  }

  return Produk(
    id: idVal?.toString(),
    sku: (json['sku'] ?? '').toString(),
    nama: (json['name'] ?? json['nama'] ?? '').toString(),
    kategori: catName,
    categoryId: catId,
    satuan: (json['unit'] ?? json['satuan'] ?? '').toString(),
    stok: int.tryParse(stockVal.toString()) ?? 0,
    hargaJual: selling != null ? (num.tryParse(selling.toString())?.toInt() ?? 0) : 0,
    hargaBeli: purchase != null ? (num.tryParse(purchase.toString())?.toInt() ?? 0) : 0,
    deskripsi: (json['description'] ?? json['deskripsi'])?.toString() ?? '',
    barcode: json['barcode']?.toString(),
  );
}


  Map<String, String> toJsonForCreate() {
    return {
      'sku': sku,
      'name': nama,
      'selling_price': (hargaJual ?? 0).toString(),
      'purchase_price': (hargaBeli ?? 0).toString(),
      'stock': stok.toString(),
      'unit': satuan,
      'category_id': categoryId,
      'description': deskripsi ?? '',
      'barcode': barcode ?? '',
    };
  }
}
