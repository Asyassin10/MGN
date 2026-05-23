<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('prenom')->nullable()->after('name');
            $table->string('poste')->nullable()->after('prenom')->index();
            $table->string('telephone')->nullable()->after('poste');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'poste', 'telephone']);
        });
    }
};
