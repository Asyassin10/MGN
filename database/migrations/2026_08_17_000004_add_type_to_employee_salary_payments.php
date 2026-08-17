<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_payments', function (Blueprint $table): void {
            $table->string('type')->default('salary')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_payments', fn (Blueprint $table) => $table->dropColumn('type'));
    }
};
