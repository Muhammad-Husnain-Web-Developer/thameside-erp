<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->string('status', 20)->default('posted');
            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedBigInteger('gross_pence')->default(0);
            $table->unsignedBigInteger('paye_pence')->default(0);
            $table->unsignedBigInteger('ni_pence')->default(0);
            $table->unsignedBigInteger('pension_pence')->default(0);
            $table->unsignedBigInteger('net_pence')->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
