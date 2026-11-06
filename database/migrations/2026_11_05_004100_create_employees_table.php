<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 20)->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('job_title');
            $table->string('contract_type', 20)->default('full_time');
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->unsignedBigInteger('annual_salary_pence')->default(0);
            $table->string('payroll_reference', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
