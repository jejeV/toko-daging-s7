# Toko Daging — Starter Code

Isi folder ini ditimpa ke project Laravel baru. Belum ada UI; yang sudah jadi adalah database, model, logika bisnis, data demo, dan test.

## Cara pasang

```bash
# 1. Buat project Laravel baru
composer create-project laravel/laravel toko-daging
cd toko-daging

# 2. Salin semua isi folder starter ini ke dalam project (timpa file yang sama)
#    app/, database/, tests/, CLAUDE.md, README.md

# 3. Atur database di .env
#    DB_CONNECTION=mysql
#    DB_DATABASE=toko_daging
#    DB_USERNAME=root
#    DB_PASSWORD=
#    APP_TIMEZONE=Asia/Jakarta   (atau set 'timezone' di config/app.php)

# 4. Buat tabel + data demo
php artisan migrate:fresh --seed

# 5. Jalankan test
php artisan test
```

Test memakai SQLite in-memory. Pastikan baris `DB_CONNECTION` = `sqlite` dan `DB_DATABASE` = `:memory:` di `phpunit.xml` aktif (tidak dikomentari).

## Akun demo

| Role | Email | Password |
| --- | --- | --- |
| Owner | owner@tokodaging.test | password |
| Kasir | kasir@tokodaging.test | password |

## Langkah berikutnya: pasang Filament

```bash
composer require filament/filament
php artisan filament:install --panels
```

Lalu buat resource per modul (Category, Product, Supplier, dst). UI hanya memanggil service di `app/Services`, contoh di halaman kasir:

```php
app(\App\Services\SaleService::class)->create(auth()->user(), $items, $paid);
```

Ikuti dokumentasi resmi Filament untuk versi yang cocok dengan versi Laravel yang terpasang.

## Isi starter

| Path | Isi |
| --- | --- |
| `database/migrations` | 12 tabel sesuai PRD (+ kolom `role`, `is_active` di users) |
| `app/Models` | 12 model dengan relasi dan cast |
| `app/Enums` | role, satuan, tipe mutasi stok, tipe penyesuaian, tipe kriteria |
| `app/Services/StockService.php` | ubah stok + catat `stock_movements` (pakai row lock) |
| `app/Services/PurchaseService.php` | stok masuk + harga beli rata-rata tertimbang |
| `app/Services/SaleService.php` | transaksi kasir, cek stok, invoice `INV-YYYYMMDD-0001` |
| `app/Services/StockAdjustmentService.php` | susut, rusak, stok opname |
| `app/Services/Saw/SawCalculator.php` | rumus SAW (PHP murni) |
| `app/Services/Saw/SupplierSawService.php` | ranking supplier dari database |
| `database/seeders` | user, produk, supplier, kriteria SAW, transaksi demo |
| `tests` | unit test SAW + feature test alur stok dan SAW |

## Catatan desain

- Kolom `products.avg_buy_price` ditambahkan (tidak ada di PRD awal). Dipakai untuk menghitung laba kotor, nilainya diupdate tiap pembelian dengan rata-rata tertimbang.
- Stok awal produk 0. Stok hanya masuk lewat pembelian, supaya `products.stock` selalu sama dengan total `stock_movements`.
- Data SAW di seeder sama dengan contoh di PRD, jadi hasilnya bisa dicocokkan dengan hitungan manual.
