<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            $table->boolean('est_sorti')->default(false)->after('statut')->index();
            $table->string('fournisseur_sortie_nom')->nullable()->after('est_sorti')->index();
            $table->dropColumn(['facture_recue', 'facture_donnee']);
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            $table->boolean('facture_recue')->default(false);
            $table->boolean('facture_donnee')->default(false);
            $table->dropIndex(['est_sorti']);
            $table->dropIndex(['fournisseur_sortie_nom']);
            $table->dropColumn(['est_sorti', 'fournisseur_sortie_nom']);
        });
    }
};
