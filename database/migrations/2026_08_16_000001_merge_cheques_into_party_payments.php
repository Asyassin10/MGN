<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['fournisseur_payments', 'client_payments', 'fournisseur_cheques', 'cheque_clients', 'cheque_fournisseurs', 'cheques'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::dropIfExists('fournisseur_payments');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('cheque_fournisseurs');
        Schema::dropIfExists('cheque_party_fournisseurs');

        if (Schema::hasTable('fournisseur_cheques')) {
            Schema::table('fournisseur_cheques', function (Blueprint $table): void {
                $table->foreignId('fournisseur_releve_compte_id')->nullable()->after('fournisseur_id')->constrained()->cascadeOnDelete();
                $table->boolean('facture_recue')->nullable()->after('statut');
                $table->boolean('facture_donnee')->nullable()->after('facture_recue');
            });
        }

        if (Schema::hasTable('cheque_clients')) {
            Schema::table('cheque_clients', function (Blueprint $table): void {
                $table->dropForeign(['client_id']);
                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            });
        }

        Schema::dropIfExists('cheque_party_clients');
    }

    public function down(): void
    {
        throw new LogicException('This clean-start migration permanently removes legacy payment and cheque data.');
    }
};
