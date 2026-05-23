<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('depot_article', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['depot_id', 'article_id']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->unique();
            $table->enum('type', ['entree', 'sortie']);
            $table->foreignId('depot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('operation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('ville')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fournisseur_factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->string('numero_facture');
            $table->date('date_facture');
            $table->decimal('montant', 12, 2);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['fournisseur_id', 'date_facture']);
        });

        Schema::create('fournisseur_cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cheque', 'effet'])->default('cheque')->index();
            $table->string('numero_cheque');
            $table->string('banque')->index();
            $table->decimal('montant', 12, 2);
            $table->string('piece_jointe')->nullable();
            $table->text('motif')->nullable();
            $table->string('tireur_signataire')->nullable();
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable()->index();
            $table->enum('statut', ['en_cours', 'en_caisse', 'impaye'])->default('en_cours')->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['fournisseur_id', 'statut']);
        });

        Schema::create('fournisseur_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fournisseur_cheque_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_paiement');
            $table->decimal('montant', 12, 2);
            $table->enum('mode', ['espece', 'cheque', 'virement']);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique('fournisseur_cheque_id');
            $table->index(['fournisseur_id', 'date_paiement']);
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('ville')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('date_entree');
            $table->decimal('montant', 12, 2);
            $table->string('description');
            $table->timestamps();
            $table->index(['client_id', 'date_entree']);
        });

        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('date_paiement');
            $table->decimal('montant', 12, 2);
            $table->enum('mode', ['espece', 'cheque', 'virement']);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'date_paiement']);
        });

        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('cheque_party_clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('cheque_party_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('cheque_clients', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['cheque', 'effet'])->default('cheque')->index();
            $table->string('numero_cheque')->index();
            $table->foreignId('client_id')->constrained('cheque_party_clients')->cascadeOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('banque')->nullable()->index();
            $table->string('piece_jointe')->nullable();
            $table->text('motif')->nullable();
            $table->string('tireur_signataire')->nullable();
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable()->index();
            $table->enum('statut', ['en_cours', 'en_caisse', 'impaye'])->default('en_cours')->index();
            $table->timestamps();
            $table->index(['client_id', 'statut']);
        });

        Schema::create('cheque_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['cheque', 'effet'])->default('cheque')->index();
            $table->string('numero_cheque')->index();
            $table->foreignId('fournisseur_id')->constrained('cheque_party_fournisseurs')->cascadeOnDelete();
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('banque')->nullable()->index();
            $table->string('piece_jointe')->nullable();
            $table->text('motif')->nullable();
            $table->string('tireur_signataire')->nullable();
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable()->index();
            $table->enum('statut', ['en_cours', 'en_caisse', 'impaye'])->default('en_cours')->index();
            $table->timestamps();
            $table->index(['fournisseur_id', 'statut']);
        });

        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['client', 'fournisseur'])->index();
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->string('tier_type')->nullable();
            $table->string('numero_cheque')->index();
            $table->string('banque')->index();
            $table->string('tireur_signataire')->nullable();
            $table->decimal('montant', 12, 2);
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable()->index();
            $table->enum('statut', ['en_cours', 'encaisse', 'impaye'])->default('en_cours')->index();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tier_id', 'tier_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('cheque_fournisseurs');
        Schema::dropIfExists('cheque_clients');
        Schema::dropIfExists('cheque_party_fournisseurs');
        Schema::dropIfExists('cheque_party_clients');
        Schema::dropIfExists('banks');
        Schema::dropIfExists('client_payments');
        Schema::dropIfExists('client_entries');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('fournisseur_payments');
        Schema::dropIfExists('fournisseur_cheques');
        Schema::dropIfExists('fournisseur_factures');
        Schema::dropIfExists('fournisseurs');
        Schema::dropIfExists('operation_lines');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('depot_article');
        Schema::dropIfExists('depots');
        Schema::dropIfExists('articles');
    }
};
