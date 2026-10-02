<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('client_telephone')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
