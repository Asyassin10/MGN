<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('source')->nullable()->after('note');
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('source')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
