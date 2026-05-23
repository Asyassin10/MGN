<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseur_payments', function (Blueprint $table) {
            $table->string('numero_cheque')->nullable()->after('fournisseur_cheque_id');
            $table->string('banque')->nullable()->after('numero_cheque');
            $table->date('date_echeance')->nullable()->after('banque');
            $table->index(['numero_cheque', 'date_echeance']);
        });
    }

    public function down(): void
    {
        Schema::table('fournisseur_payments', function (Blueprint $table) {
            $table->dropIndex(['numero_cheque', 'date_echeance']);
            $table->dropColumn(['numero_cheque', 'banque', 'date_echeance']);
        });
    }
};
