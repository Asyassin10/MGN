<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseur_releve_comptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->string('code_client');
            $table->date('date_releve');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['fournisseur_id', 'date_releve']);
        });

        DB::table('fournisseur_factures')->delete();
        DB::table('fournisseur_payments')->delete();

        Schema::table('fournisseur_factures', function (Blueprint $table) {
            $table->foreignId('fournisseur_releve_compte_id')
                ->after('fournisseur_id')
                ->constrained('fournisseur_releve_comptes')
                ->cascadeOnDelete();

            $table->index(['fournisseur_releve_compte_id', 'date_facture']);
        });

        Schema::table('fournisseur_payments', function (Blueprint $table) {
            $table->foreignId('fournisseur_releve_compte_id')
                ->after('fournisseur_id')
                ->constrained('fournisseur_releve_comptes')
                ->cascadeOnDelete();

            $table->index(['fournisseur_releve_compte_id', 'date_paiement']);
        });
    }

    public function down(): void
    {
        Schema::table('fournisseur_payments', function (Blueprint $table) {
            $table->dropIndex(['fournisseur_releve_compte_id', 'date_paiement']);
            $table->dropConstrainedForeignId('fournisseur_releve_compte_id');
        });

        Schema::table('fournisseur_factures', function (Blueprint $table) {
            $table->dropIndex(['fournisseur_releve_compte_id', 'date_facture']);
            $table->dropConstrainedForeignId('fournisseur_releve_compte_id');
        });

        Schema::dropIfExists('fournisseur_releve_comptes');
    }
};
