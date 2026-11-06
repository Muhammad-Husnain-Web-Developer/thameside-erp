<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_bills', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->date('bill_date');
            $table->date('due_date');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('subtotal_pence')->default(0);
            $table->unsignedBigInteger('vat_pence')->default(0);
            $table->unsignedBigInteger('total_pence')->default(0);
            $table->unsignedBigInteger('amount_paid_pence')->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_bills');
    }
};
