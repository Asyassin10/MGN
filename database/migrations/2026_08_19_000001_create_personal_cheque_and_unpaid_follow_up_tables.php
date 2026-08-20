<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_cheques', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('cheque');
            $table->string('numero_cheque');
            $table->string('donneur');
            $table->string('banque')->nullable();
            $table->string('tireur_signataire')->nullable();
            $table->decimal('montant', 12, 2);
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable();
            $table->string('statut')->default('en_cours');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('unpaid_cheque_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->date('promised_payment_date')->nullable();
            $table->date('contact_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unpaid_cheque_follow_ups');
        Schema::dropIfExists('personal_cheques');
    }
};
