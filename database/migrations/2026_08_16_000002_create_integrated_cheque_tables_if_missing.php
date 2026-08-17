<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fournisseur_cheques')) {
            Schema::create('fournisseur_cheques', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fournisseur_releve_compte_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('type')->default('cheque');
                $table->string('numero_cheque');
                $table->string('banque');
                $table->decimal('montant', 12, 2);
                $table->string('piece_jointe')->nullable();
                $table->text('motif')->nullable();
                $table->string('tireur_signataire')->nullable();
                $table->date('date_emission')->nullable();
                $table->date('date_echeance')->nullable();
                $table->string('statut')->default('en_cours');
                $table->boolean('facture_recue')->nullable();
                $table->boolean('facture_donnee')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cheque_clients')) {
            Schema::create('cheque_clients', function (Blueprint $table): void {
                $table->id();
                $table->string('type')->default('cheque');
                $table->string('numero_cheque')->index();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('montant', 12, 2);
                $table->string('banque')->nullable();
                $table->string('piece_jointe')->nullable();
                $table->text('motif')->nullable();
                $table->string('tireur_signataire')->nullable();
                $table->date('date_emission')->nullable();
                $table->date('date_echeance')->nullable();
                $table->string('statut')->default('en_cours');
                $table->boolean('facture_recue')->nullable();
                $table->boolean('facture_donnee')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Compatibility tables are intentionally retained with the application schema.
    }
};
