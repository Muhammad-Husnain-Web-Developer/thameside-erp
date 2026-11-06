<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('ordered_at');
            $table->date('fulfilled_at')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->unsignedBigInteger('subtotal_pence')->default(0);
            $table->unsignedBigInteger('vat_pence')->default(0);
            $table->unsignedBigInteger('total_pence')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('ordered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
