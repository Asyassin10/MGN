<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cheque_clients', 'cheque_fournisseurs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('facture_donnee')->nullable()->after('facture_recue');
            });
        }
    }

    public function down(): void
    {
        foreach (['cheque_clients', 'cheque_fournisseurs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('facture_donnee');
            });
        }
    }
};
