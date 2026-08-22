<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDebtBookController;
use App\Http\Controllers\Admin\AdminExpenditureController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminReceivableController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminRecommendationController;
use App\Http\Controllers\Admin\AdminStoreSettingController;
use App\Http\Controllers\Admin\PurchaseController; // <-- tambahkan import PurchaseController
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Route publik (login) dan route admin yang diproteksi.
| Semua route admin berada di prefix /admin dan memakai middleware auth:web + admin.
|
*/

# Public routes (guest)
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
});

# Redirect root
Route::get('/', function () {
    if (auth('web')->check()) {
        return redirect('/admin/dashboard');
    }
    return redirect('/login');
});

# Protected admin routes
Route::middleware(['auth:web', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::resource('products', AdminProductController::class);
    Route::patch('products/{product}/toggle-active', [AdminProductController::class, 'toggleActive'])
        ->name('products.toggle-active');

    // Categories
    Route::resource('categories', AdminCategoryController::class);
    Route::patch('categories/{category}/toggle-active', [AdminCategoryController::class, 'toggleActive'])
        ->name('categories.toggle-active');
    Route::post('categories/quick-store', [AdminCategoryController::class, 'quickStore'])
        ->name('categories.quick-store');

    // Expenditures (resource lengkap: index, create, store, show, edit, update, destroy)
    Route::resource('expenditures', AdminExpenditureController::class);

    // Purchases (resource lengkap) - penting agar route('purchases.edit') tersedia
    Route::resource('purchases', PurchaseController::class);

    // Debt book (buku utang)
    Route::prefix('debt-book')->name('debt-book.')->group(function () {
        Route::get('/', [AdminDebtBookController::class, 'index'])->name('index');

        // PENTING: route riwayat & create harus di ATAS route /{customer},
        // supaya kata "riwayat" / "create" tidak tertangkap sebagai parameter {customer}.
        Route::get('/riwayat', [AdminDebtBookController::class, 'riwayat'])->name('riwayat');

        Route::get('/create', [AdminDebtBookController::class, 'create'])->name('create');
        Route::post('/', [AdminDebtBookController::class, 'store'])->name('store');

        Route::get('/{customer}', [AdminDebtBookController::class, 'show'])->name('show');
    });

    // Customers
    Route::resource('customers', AdminCustomerController::class);
    Route::get('customers/{customer}/receivables', [AdminCustomerController::class, 'receivables'])
        ->name('customers.receivables');

    // Recommendations
    Route::get('/recommendations', [AdminRecommendationController::class, 'index'])
        ->name('recommendations.index');

    // Reports
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/{date}', [AdminReportController::class, 'salesDetail'])
        ->name('reports.sales.detail')
        ->where('date', '\d{4}-\d{2}-\d{2}'); // pastikan formatnya YYYY-MM-DD
    Route::get('/reports/products', [AdminReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/receivables', [AdminReportController::class, 'receivables'])->name('reports.receivables');
    Route::get('reports/sales/excel', [AdminReportController::class, 'salesExcel'])->name('reports.sales.excel');

    // Profile / store settings
    Route::get('/profile', [AdminStoreSettingController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminStoreSettingController::class, 'update'])->name('profile.update');

    // Logout
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});
