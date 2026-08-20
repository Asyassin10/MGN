<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->string('mode_paiement')->nullable()->after('date_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('cheques_impayes', function (Blueprint $table): void {
            $table->dropColumn('mode_paiement');
        });
    }
};
