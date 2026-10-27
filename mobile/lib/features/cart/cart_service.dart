import '../../core/api/api_service.dart';

class CartService {
  static List<Map<String, dynamic>> cart = [];

  /// Kunci unik per (produk + satuan).
  static String cartKey(dynamic productId, String unit) => '$productId|$unit';

  static void addToCart(
    dynamic item, {
    required String unit,
    required num sellingPrice,
    num? purchasePrice,
  }) {
    final key = cartKey(item['id'], unit);
    final index = cart.indexWhere((e) => e['cart_key'] == key);

    if (index != -1) {
      cart[index]['qty'] = (cart[index]['qty'] as int) + 1;
    } else {
      cart.add({
        ...Map<String, dynamic>.from(item),
        'cart_key': key,
        'unit': unit,
        'selling_price': sellingPrice,
        'purchase_price': purchasePrice,
        'qty': 1,
      });
    }
  }

  static void removeByKey(String cartKey) {
    cart.removeWhere((e) => e['cart_key'] == cartKey);
  }

  static void removeFromCart(int index) => cart.removeAt(index);

  static void clearCart() => cart.clear();

  static int get totalHarga {
    final total = cart.fold<num>(0, (sum, item) {
      final harga = num.tryParse(item['selling_price'].toString()) ?? 0;
      return sum + harga * (item['qty'] as int);
    });
    return total.round();
  }

  static Future<Map<String, dynamic>> checkout({
    String paymentType = 'cash',
    int? customerId,
    String? dueDate,
    String? notes,
  }) async {
    if (cart.isEmpty) throw Exception('Keranjang kosong, tidak dapat checkout.');

    const allowed = ['cash', 'debt', 'qris', 'transfer'];
    if (!allowed.contains(paymentType)) {
      throw Exception('Tipe pembayaran tidak valid: $paymentType');
    }
    if (paymentType == 'debt' && customerId == null) {
      throw Exception('Pelanggan wajib dipilih untuk transaksi utang.');
    }

    final items = cart.map((item) => {
          'product_id': item['id'],
          'unit': item['unit'],
          'quantity': item['qty'],
          'unit_price': item['selling_price'],
        }).toList();

    final response = await ApiService.post('sales-transactions', {
      'customer_id': customerId,
      'items': items,
      'total_amount': totalHarga,
      'payment_type': paymentType,
      'due_date': dueDate,
      'notes_receivable': notes,
    });

    cart.clear();
    return response is Map
        ? Map<String, dynamic>.from(response)
        : <String, dynamic>{};
  }
}