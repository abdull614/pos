# POS System - Laravel Admin API

Sistem Point of Sale (POS) dengan backend Laravel untuk admin dan API untuk aplikasi Flutter kasir.

## Tech Stack

- **Framework:** Laravel 13
- **Database:** MySQL (toko_pos)
- **Authentication:** Laravel Sanctum
- **PHP:** 8.3+

## Database Setup

### Struktur Tabel

1. **users** - Admin & Kasir (role-based)
2. **categories** - Kategori produk
3. **products** - Master produk dengan stock management
4. **customers** - Data pelanggan
5. **sales_transactions** - Transaksi penjualan
6. **sale_items** - Detail item per transaksi
7. **customer_receivables** - Buku utang pelanggan
8. **payment_history** - Riwayat pembayaran
9. **product_recommendations** - Rekomendasi produk dari admin

### Relationships

```
User (1) -> (N) SalesTransaction
User (1) -> (N) PaymentHistory
User (1) -> (N) ProductRecommendation

Category (1) -> (N) Products

Product (1) -> (N) SaleItems
Product (1) -> (N) ProductRecommendation

Customer (1) -> (N) SalesTransaction
Customer (1) -> (N) CustomerReceivable
Customer (1) -> (N) PaymentHistory

SalesTransaction (1) -> (N) SaleItems
SalesTransaction (1) -> (1) CustomerReceivable

CustomerReceivable (1) -> (N) PaymentHistory
```

## Installation

1. **Install Dependencies**

    ```bash
    cd admin
    composer install
    ```

2. **Setup Database**

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

    Ini akan membuat:
    - Admin user: `admin@toko.local` (password: `password123`)
    - Kasir users: `kasir1@toko.local`, `kasir2@toko.local`
    - Sample categories & products
    - Sample customers

3. **Run Server**
    ```bash
    php artisan serve
    ```
    Server akan berjalan di `http://localhost:8000`

## API Endpoints

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

**Register**

```
POST /auth/register
{
  "name": "Kasir Baru",
  "email": "kasir_baru@toko.local",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "kasir",
  "phone": "081234567890",
  "address": "Jl. Test No. 1"
}
```

**Login**

```
POST /auth/login
{
  "email": "kasir1@toko.local",
  "password": "password123"
}

Response:
{
  "message": "Login successful",
  "user": {...},
  "token": "token_string_here"
}
```

**Get Profile**

```
GET /auth/me
Headers: Authorization: Bearer {token}
```

**Logout**

```
POST /auth/logout
Headers: Authorization: Bearer {token}
```

### Categories (Admin Only)

```
GET    /categories              # List all
POST   /categories              # Create
GET    /categories/{id}         # Show
PUT    /categories/{id}         # Update
DELETE /categories/{id}         # Delete
```

### Products

```
GET    /products                # List with pagination
GET    /products?search=xyz     # Search by name/SKU
GET    /products?category_id=1  # Filter by category
GET    /products/low-stock      # Products below min_stock
GET    /products/{id}           # Show details
POST   /products                # Create (Admin only)
PUT    /products/{id}           # Update (Admin only)
DELETE /products/{id}           # Delete (Admin only)
```

### Customers

```
GET    /customers                           # List
GET    /customers?search=name               # Search
GET    /customers/debtors                   # Get customers with debt
GET    /customers?with_debt=true            # Filter with debt
GET    /customers/{id}                      # Show with receivables
POST   /customers                           # Create
PUT    /customers/{id}                      # Update
DELETE /customers/{id}                      # Delete
```

### Sales Transactions (Kasir)

```
GET    /sales-transactions                          # List
GET    /sales-transactions?date_from=2024-01-01    # Filter by date
GET    /sales-transactions?payment_type=cash       # Filter by payment type
GET    /sales-transactions/reports/today           # Today's report
GET    /sales-transactions/{id}                    # Show details
POST   /sales-transactions                         # Create transaction
```

**Create Transaction Format**

```json
{
    "customer_id": null,
    "payment_type": "cash",
    "discount": 0,
    "tax": 0,
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "unit_price": 25000
        },
        {
            "product_id": 2,
            "quantity": 1,
            "unit_price": 50000
        }
    ],
    "notes": "Order dari customer"
}
```

### Payments

```
GET    /payments/receivables                        # Get unpaid receivables
GET    /payments/history                            # Payment history
POST   /payments/record                             # Record payment
```

**Record Payment**

```json
{
    "customer_receivable_id": 1,
    "amount": 50000,
    "payment_method": "cash",
    "reference": "payment-ref-001",
    "notes": "Pembayaran sebagian"
}
```

### Recommendations (Admin Only)

```
GET    /recommendations          # Get active recommendations
POST   /recommendations          # Create recommendation
PUT    /recommendations/{id}     # Update
DELETE /recommendations/{id}     # Delete
```

### Dashboard

```
GET    /dashboard/admin          # Admin dashboard stats (Admin only)
GET    /dashboard/kasir          # Kasir dashboard stats (Kasir only)
```

## Middleware & Authorization

- **Admin Only:** Categories, Product CRUD, Customer CRUD, Recommendations
- **Kasir Only:** Create sales transactions
- **Public Read:** Products, Customers (list), Payments (read)

## Roles

- **admin:** Full access ke management semua fitur
- **kasir:** Hanya bisa membuat transaksi, lihat produk & pelanggan

## Features Implemented

### Admin Dashboard

- ✅ Total penjualan hari ini
- ✅ Total utang pelanggan
- ✅ Top 5 produk terjual hari ini
- ✅ Count produk stok rendah

### Kasir Dashboard

- ✅ Total penjualan saya hari ini
- ✅ Jumlah transaksi pending
- ✅ Total penjualan utang hari ini

### Manajemen Produk (Admin)

- ✅ CRUD produk
- ✅ Kategorisasi
- ✅ Stock management
- ✅ Low stock tracking

### Transaksi (Kasir)

- ✅ Create sales transaction
- ✅ Add items ke transaction
- ✅ Calculate discount & tax
- ✅ Auto-decrement stock
- ✅ Cash & debt payment types

### Buku Utang (Admin & Kasir)

- ✅ View customer receivables
- ✅ Record payment
- ✅ Track payment history
- ✅ Update customer debt status

### Rekomendasi (Admin)

- ✅ Create product recommendations
- ✅ Set priority
- ✅ Get active recommendations

## Next Steps

1. **Setup Flutter Kasir App**
    - Implement UI dengan design yang diberikan
    - Integrate API endpoints
    - Authentication & token management

2. **Admin Dashboard Web/UI**
    - Dapat menggunakan Laravel Blade atau separate frontend (Vue/React)

3. **Testing**
    - Validate API endpoints
    - Test business logic

4. **Database Optimization**
    - Indexing pada kolom yang sering di-query
    - Add caching untuk data statis

## Environment Variables

Key variables di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=toko_pos
DB_USERNAME=root
DB_PASSWORD=

AUTH_GUARD=sanctum
APP_NAME=TokoPos
APP_URL=http://localhost:8000
```

## Credentials

**Admin:**

- Email: `admin@toko.local`
- Password: `password123`

**Kasir 1:**

- Email: `kasir1@toko.local`
- Password: `password123`

**Kasir 2:**

- Email: `kasir2@toko.local`
- Password: `password123`
