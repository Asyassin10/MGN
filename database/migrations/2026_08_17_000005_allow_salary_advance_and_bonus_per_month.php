<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_payments', function (Blueprint $table): void {
            $table->dropUnique(['employee_id', 'month']);
            $table->unique(['employee_id', 'month', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_payments', function (Blueprint $table): void {
            $table->dropUnique(['employee_id', 'month', 'type']);
            $table->unique(['employee_id', 'month']);
        });
    }
};
