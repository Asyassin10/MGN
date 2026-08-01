<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cheque_clients', 'cheque_fournisseurs', 'cheques'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('facture_recue')->nullable()->change();
            });

            DB::table($tableName)->where('facture_recue', false)->update(['facture_recue' => null]);
        }
    }

    public function down(): void
    {
        foreach (['cheque_clients', 'cheque_fournisseurs', 'cheques'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('facture_recue')->default(false)->nullable(false)->change();
            });
        }
    }
};
