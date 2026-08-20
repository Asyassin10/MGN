<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->dropColumn(['facture_recue', 'facture_donnee']);
        });
    }

    public function down(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->boolean('facture_recue')->default(false);
            $table->boolean('facture_donnee')->default(false);
        });
    }
};
