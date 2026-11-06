<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Purchasing\Actions\CreatePurchaseOrder;
use App\Modules\Purchasing\Actions\ReceivePurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoPurchasingSeeder extends Seeder
{
    public function run(): void
    {
        if (Supplier::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'amelia.hart@thamesidekitchen.co.uk')->first()
            ?? User::query()->first();

        $suppliers = [
            ['code' => 'SUP-0001', 'name' => 'Beaumont appliances UK', 'contact_name' => 'Priya Raghavan', 'email' => 'trade@beaumont-appliances.co.uk', 'phone' => '+44 161 496 0182', 'city' => 'Manchester', 'postcode' => 'M17 1WA', 'vat_number' => 'GB 318 4420 91', 'payment_terms_days' => 30],
            ['code' => 'SUP-0002', 'name' => 'Calder Domestic Ltd', 'contact_name' => 'Tom Whitfield', 'email' => 'orders@calderdomestic.co.uk', 'phone' => '+44 113 887 2210', 'city' => 'Leeds', 'postcode' => 'LS10 1AB', 'vat_number' => 'GB 774 1902 33', 'payment_terms_days' => 45],
            ['code' => 'SUP-0003', 'name' => 'Ashford Refrigeration', 'contact_name' => 'Grace Okafor', 'email' => 'sales@ashfordrefrigeration.co.uk', 'phone' => '+44 20 3355 7714', 'city' => 'London', 'postcode' => 'E16 2QU', 'vat_number' => 'GB 229 5561 82', 'payment_terms_days' => 30],
            ['code' => 'SUP-0004', 'name' => 'Wren Brassware & Sinks', 'contact_name' => 'Daniel Mercer', 'email' => 'trade@wrenbrassware.co.uk', 'phone' => '+44 121 448 9930', 'city' => 'Birmingham', 'postcode' => 'B7 4BB', 'vat_number' => 'GB 550 8823 10', 'payment_terms_days' => 14],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier + ['is_active' => true]);
        }

        $beaumont = Supplier::query()->where('code', 'SUP-0001')->first();
        $wren = Supplier::query()->where('code', 'SUP-0004')->first();

        $hood = Product::query()->where('tracks_serial', false)->where('name', 'like', '%Chimney extractor%')->first();
        $sink = Product::query()->where('tracks_serial', false)->where('name', 'like', '%1.5 bowl%')->first();
        $toaster = Product::query()->where('tracks_serial', false)->where('name', 'like', '%toaster%')->first();

        // A delivered order: stock in, bill posted to the ledger.
        $delivered = CreatePurchaseOrder::run([
            'supplier_id' => $wren->id,
            'ordered_at' => now()->subDays(18)->toDateString(),
            'expected_at' => now()->subDays(11)->toDateString(),
            'notes' => 'Replenish sink and tap lines for the autumn trade show.',
            'lines' => [
                ['product_id' => $sink->id, 'quantity' => 12, 'unit_cost' => '62.00'],
                ['product_id' => $toaster->id, 'quantity' => 20, 'unit_cost' => '21.00'],
            ],
        ], $admin);

        ReceivePurchaseOrder::run($delivered, ['received_at' => now()->subDays(12)->toDateString()], $admin);

        // An open order still on its way from Manchester.
        CreatePurchaseOrder::run([
            'supplier_id' => $beaumont->id,
            'ordered_at' => now()->subDays(3)->toDateString(),
            'expected_at' => now()->addDays(9)->toDateString(),
            'notes' => 'Hood stock for the Greenwich kitchen contract.',
            'lines' => [
                ['product_id' => $hood->id, 'quantity' => 15, 'unit_cost' => '185.00'],
            ],
        ], $admin);

        expect(PurchaseOrder::query()->count())->toBeGreaterThan(0);
    }
}
