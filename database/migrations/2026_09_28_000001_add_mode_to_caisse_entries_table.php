<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caisse_entries', function (Blueprint $table) {
            $table->string('mode')->nullable()->after('montant');
        });
    }

    public function down(): void
    {
        Schema::table('caisse_entries', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
