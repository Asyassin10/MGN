<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('unpaid_cheque_follow_ups');
        Schema::dropIfExists('personal_cheques');
        Schema::dropIfExists('cheques_impayes');
        Schema::dropIfExists('cheques');

        Schema::create('cheques', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('cheque')->index();
            $table->string('numero_cheque')->index();
            $table->string('client_nom')->index();
            $table->string('tireur_signataire')->nullable();
            $table->date('date_emission');
            $table->date('date_echeance')->index();
            $table->string('statut')->default('en_cours')->index();
            $table->boolean('facture_recue')->default(false);
            $table->boolean('facture_donnee')->default(false);
            $table->decimal('montant', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('cheques_impayes', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('cheque')->index();
            $table->string('numero_cheque')->index();
            $table->string('fournisseur_nom')->index();
            $table->string('tireur_signataire')->nullable();
            $table->date('date_remise')->index();
            $table->string('statut')->default('impaye')->index();
            $table->date('date_paiement')->nullable();
            $table->boolean('facture_recue')->default(false);
            $table->boolean('facture_donnee')->default(false);
            $table->decimal('montant', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques_impayes');
        Schema::dropIfExists('cheques');
    }
};
