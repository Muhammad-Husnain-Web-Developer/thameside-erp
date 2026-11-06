<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: there is deliberately no quantity column. Stock on hand is
        // always derived from the stock_movements ledger.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand', 80)->nullable();
            $table->string('unit', 20)->default('each');
            $table->unsignedInteger('cost_price_pence')->default(0);
            $table->unsignedInteger('sell_price_pence')->default(0);
            $table->unsignedSmallInteger('vat_rate_bps')->default(2000);
            $table->boolean('tracks_serial')->default(false);
            $table->unsignedInteger('reorder_level')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
