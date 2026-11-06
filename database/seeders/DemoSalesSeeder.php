<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordCustomerReceipt;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Seeder;

class DemoSalesSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'amelia.hart@thamesidekitchen.co.uk')->first()
            ?? User::query()->first();

        $customers = [
            ['code' => 'CUS-0001', 'name' => 'Borough Kitchen Studios', 'contact_name' => 'Ellie Marsh', 'email' => 'ellie@boroughkitchen.co.uk', 'phone' => '+44 20 7407 1188', 'address' => '18 Bermondsey Street', 'city' => 'London', 'postcode' => 'SE1 3TQ', 'credit_limit' => 2500000, 'payment_terms_days' => 30],
            ['code' => 'CUS-0002', 'name' => 'Greenwich Building Supplies', 'contact_name' => 'Marcus Bell', 'email' => 'trade@greenwichbuild.co.uk', 'phone' => '+44 20 8858 4410', 'address' => 'Tunnel Avenue', 'city' => 'London', 'postcode' => 'SE10 0PL', 'credit_limit' => 1500000, 'payment_terms_days' => 45],
            ['code' => 'CUS-0003', 'name' => 'Clapham Interiors Ltd', 'contact_name' => 'Sofia Nunes', 'email' => 'orders@claphaminteriors.co.uk', 'phone' => '+44 20 7622 9081', 'address' => '92 Northcote Road', 'city' => 'London', 'postcode' => 'SW11 6PJ', 'credit_limit' => 1000000, 'payment_terms_days' => 30],
            ['code' => 'CUS-0004', 'name' => 'Hackney Kitchen Co.', 'contact_name' => 'Owen Price', 'email' => 'hello@hackneykitchen.co', 'phone' => '+44 20 8986 5533', 'address' => '14 Wilton Way', 'city' => 'London', 'postcode' => 'E8 1BG', 'credit_limit' => 750000, 'payment_terms_days' => 14],
            ['code' => 'CUS-0005', 'name' => 'Riverside Property Group', 'contact_name' => 'Nadia Khan', 'email' => 'procurement@riversidepg.co.uk', 'phone' => '+44 20 7001 2244', 'address' => '1 Bankside', 'city' => 'London', 'postcode' => 'SE1 9RB', 'credit_limit' => 5000000, 'payment_terms_days' => 60],
        ];

        foreach ($customers as $customer) {
            $data = $customer;
            $limit = $data['credit_limit'];
            unset($data['credit_limit']);

            Customer::create($data + ['credit_limit_pence' => $limit, 'is_active' => true]);
        }

        $borough = Customer::query()->where('code', 'CUS-0001')->first();
        $greenwich = Customer::query()->where('code', 'CUS-0002')->first();
        $clapham = Customer::query()->where('code', 'CUS-0003')->first();

        $sink = Product::query()->where('name', 'like', '%1.5 bowl%')->first();
        $taps = Product::query()->where('name', 'like', '%mixer tap%')->first();
        $toaster = Product::query()->where('name', 'like', '%toaster%')->first();
        $kettle = Product::query()->where('name', 'like', '%kettle%')->first();
        $hood = Product::query()->where('name', 'like', '%Chimney extractor%')->first();

        // Invoiced in full and settled.
        $settled = CreateSalesOrder::run([
            'customer_id' => $borough->id,
            'ordered_at' => now()->subDays(24)->toDateString(),
            'notes' => 'Showroom refresh — deliver to the Bermondsey yard.',
            'lines' => [
                ['product_id' => $sink->id, 'quantity' => 4, 'unit_price' => '115.00'],
                ['product_id' => $taps->id, 'quantity' => 4, 'unit_price' => '89.00'],
                ['product_id' => $toaster->id, 'quantity' => 6, 'unit_price' => '39.00'],
            ],
        ], $admin);

        $invoice = CreateInvoice::run($settled, ['invoice_date' => now()->subDays(22)->toDateString()], $admin);
        RecordCustomerReceipt::run($invoice, ['amount' => number_format($invoice->total_pence / 100, 2, '.', ''), 'received_at' => now()->subDays(9)->toDateString(), 'reference' => 'BACS 4471'], $admin);

        // Invoiced, part paid and now overdue.
        $partPaid = CreateSalesOrder::run([
            'customer_id' => $greenwich->id,
            'ordered_at' => now()->subDays(40)->toDateString(),
            'notes' => 'Site cabin fit-out, Tunnel Avenue.',
            'lines' => [
                ['product_id' => $kettle->id, 'quantity' => 18, 'unit_price' => '34.00'],
                ['product_id' => $toaster->id, 'quantity' => 12, 'unit_price' => '39.00'],
                ['product_id' => $hood->id, 'quantity' => 2, 'unit_price' => '299.00'],
            ],
        ], $admin);

        $overdue = CreateInvoice::run($partPaid, ['invoice_date' => now()->subDays(38)->toDateString(), 'due_date' => now()->subDays(8)->toDateString()], $admin);
        RecordCustomerReceipt::run($overdue, ['amount' => '500.00', 'received_at' => now()->subDays(20)->toDateString(), 'reference' => 'BACS 4519'], $admin);

        // Still waiting to be invoiced.
        CreateSalesOrder::run([
            'customer_id' => $clapham->id,
            'ordered_at' => now()->subDays(2)->toDateString(),
            'notes' => 'Awaiting the customer site survey before delivery.',
            'lines' => [
                ['product_id' => $sink->id, 'quantity' => 6, 'unit_price' => '115.00'],
                ['product_id' => $taps->id, 'quantity' => 6, 'unit_price' => '89.00'],
            ],
        ], $admin);
    }
}
