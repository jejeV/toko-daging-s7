<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SawController;
use App\Http\Controllers\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

// Beranda / Landing Page
Route::get('/', fn () => view('welcome'))->name('home');

// Autentikasi
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login/{user}', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rute yang membutuhkan Login
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS / Kasir
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/{sale}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');

    // Produk & Stok
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');

    // Pembelian (Stok Masuk)
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');

    // Penyesuaian Stok (Susut, Rusak, Opname)
    Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');

    // SPK Produk Terlaris & Terbaik (Metode SAW)
    Route::get('/saw', [SawController::class, 'index'])->name('saw.index');
    Route::get('/saw/scores', [SawController::class, 'scores'])->name('saw.scores');
    Route::post('/saw/scores', [SawController::class, 'updateScores'])->name('saw.scores.update');
    Route::post('/saw/sync', [SawController::class, 'syncFromSales'])->name('saw.sync');
    Route::get('/saw/sub-kriteria', [SawController::class, 'subCriteria'])->name('saw.sub-criteria');
    Route::post('/saw/sub-kriteria', [SawController::class, 'updateSubCriteria'])->name('saw.sub-criteria.update');
    Route::post('/saw/sub-kriteria/reset', [SawController::class, 'resetSubCriteria'])->name('saw.sub-criteria.reset');

    // Laporan penjualan (grafik) & ekspor CSV
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/produk.csv', [ReportController::class, 'productsCsv'])->name('reports.products-csv');

    // Riwayat Penjualan
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
});
