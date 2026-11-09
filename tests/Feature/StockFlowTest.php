<?php

namespace Tests\Feature;

use App\Enums\AdjustmentType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $kasir;
    private Supplier $supplier;
    private Product $daging;
    private Product $sosis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => UserRole::Owner]);
        $this->kasir = User::factory()->create(['role' => UserRole::Kasir]);
        $this->supplier = Supplier::create(['name' => 'Supplier Uji']);

        $category = Category::create(['name' => 'Sapi']);

        $this->daging = Product::create([
            'category_id' => $category->id, 'code' => 'T-SPI', 'name' => 'Daging sapi',
            'unit' => 'kg', 'sell_price' => 140000, 'min_stock' => 1,
        ]);

        $this->sosis = Product::create([
            'category_id' => $category->id, 'code' => 'T-SOS', 'name' => 'Sosis',
            'unit' => 'pcs', 'sell_price' => 40000, 'min_stock' => 5,
        ]);

        app(PurchaseService::class)->create($this->owner, $this->supplier->id, '2026-10-01', [
            ['product_id' => $this->daging->id, 'qty' => 10, 'buy_price' => 100000],
            ['product_id' => $this->sosis->id, 'qty' => 20, 'buy_price' => 30000],
        ]);
    }

    private function assertLedgerMatches(Product $product): void
    {
        $product->refresh();
        $ledger = (float) StockMovement::where('product_id', $product->id)->sum('qty');

        $this->assertEqualsWithDelta((float) $product->stock, $ledger, 0.0005, 'Stok produk tidak sama dengan stock_movements');
    }

    public function test_pembelian_menambah_stok_dan_menghitung_harga_beli_rata_rata(): void
    {
        app(PurchaseService::class)->create($this->owner, $this->supplier->id, '2026-10-02', [
            ['product_id' => $this->daging->id, 'qty' => 10, 'buy_price' => 120000],
        ]);

        $this->daging->refresh();

        $this->assertEquals(20, (float) $this->daging->stock);
        $this->assertEquals(110000, (float) $this->daging->avg_buy_price); // (10*100rb + 10*120rb) / 20
        $this->assertLedgerMatches($this->daging);
    }

    public function test_penjualan_desimal_mengurangi_stok_dan_menghitung_total(): void
    {
        $sale = app(SaleService::class)->create($this->kasir, [
            ['product_id' => $this->daging->id, 'qty' => 0.75],
            ['product_id' => $this->sosis->id, 'qty' => 2],
        ], 200000, Carbon::parse('2026-10-26 10:00'));

        $this->assertSame('INV-20261026-0001', $sale->invoice_no);
        $this->assertEquals(185000, (float) $sale->total);   // 0,75 x 140rb + 2 x 40rb
        $this->assertEquals(15000, (float) $sale->change);
        $this->assertEquals(100000, (float) $sale->items->first()->buy_price);

        $this->assertEquals(9.25, (float) $this->daging->fresh()->stock);
        $this->assertEquals(18, (float) $this->sosis->fresh()->stock);
        $this->assertLedgerMatches($this->daging);
        $this->assertLedgerMatches($this->sosis);
    }

    public function test_nomor_invoice_berurutan_dan_reset_per_hari(): void
    {
        $service = app(SaleService::class);
        $item = [['product_id' => $this->sosis->id, 'qty' => 1]];

        $a = $service->create($this->kasir, $item, 40000, Carbon::parse('2026-10-26 09:00'));
        $b = $service->create($this->kasir, $item, 40000, Carbon::parse('2026-10-26 09:05'));
        $c = $service->create($this->kasir, $item, 40000, Carbon::parse('2026-10-27 08:00'));

        $this->assertSame('INV-20261026-0001', $a->invoice_no);
        $this->assertSame('INV-20261026-0002', $b->invoice_no);
        $this->assertSame('INV-20261027-0001', $c->invoice_no);
    }

    public function test_penjualan_ditolak_jika_stok_kurang_dan_tidak_ada_yang_tersimpan(): void
    {
        try {
            app(SaleService::class)->create($this->kasir, [
                ['product_id' => $this->sosis->id, 'qty' => 1],
                ['product_id' => $this->daging->id, 'qty' => 10.5],
            ], 5000000);
            $this->fail('Seharusnya melempar InsufficientStockException');
        } catch (InsufficientStockException) {
        }

        $this->assertSame(0, Sale::count());
        $this->assertEquals(20, (float) $this->sosis->fresh()->stock);
        $this->assertEquals(10, (float) $this->daging->fresh()->stock);
    }

    public function test_produk_sama_di_dua_baris_dicek_total_qty_nya(): void
    {
        $this->expectException(InsufficientStockException::class);

        app(SaleService::class)->create($this->kasir, [
            ['product_id' => $this->daging->id, 'qty' => 6],
            ['product_id' => $this->daging->id, 'qty' => 5],
        ], 5000000);
    }

    public function test_penjualan_ditolak_jika_uang_bayar_kurang(): void
    {
        $this->expectException(BusinessRuleException::class);

        app(SaleService::class)->create($this->kasir, [
            ['product_id' => $this->daging->id, 'qty' => 1],
        ], 100000);
    }

    public function test_produk_pcs_tidak_boleh_qty_desimal(): void
    {
        $this->expectException(BusinessRuleException::class);

        app(SaleService::class)->create($this->kasir, [
            ['product_id' => $this->sosis->id, 'qty' => 1.5],
        ], 100000);
    }

    public function test_susut_mengurangi_stok(): void
    {
        $adj = app(StockAdjustmentService::class)
            ->record($this->owner, $this->daging->id, AdjustmentType::Susut, 0.4, 'Susut air');

        $this->assertEquals(-0.4, (float) $adj->qty);
        $this->assertEquals(9.6, (float) $this->daging->fresh()->stock);
        $this->assertLedgerMatches($this->daging);
    }

    public function test_opname_menyamakan_stok_dengan_stok_fisik(): void
    {
        $adj = app(StockAdjustmentService::class)
            ->record($this->owner, $this->daging->id, AdjustmentType::Opname, 9.85, 'Opname mingguan');

        $this->assertEquals(-0.15, (float) $adj->qty);
        $this->assertEquals(9.85, (float) $this->daging->fresh()->stock);
        $this->assertLedgerMatches($this->daging);
    }
}
