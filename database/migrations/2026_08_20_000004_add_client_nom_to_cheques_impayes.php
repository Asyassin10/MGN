<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->string('client_nom')->nullable()->after('fournisseur_nom')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->dropColumn('client_nom');
        });
    }
};
