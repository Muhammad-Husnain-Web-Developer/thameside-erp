<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nominal_account_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('debit_pence')->default(0);
            $table->unsignedBigInteger('credit_pence')->default(0);
            $table->timestamps();

            $table->index('nominal_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
