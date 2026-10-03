<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->string('status', 20)->default('en_attente')->after('reference');
            $table->timestamp('validated_at')->nullable()->after('note');
        });

        Schema::table('bon_livraison_lines', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('prix');
        });

        // Devis created before this change already removed their stock when saved: they are validated.
        DB::table('bon_livraisons')->update(['status' => 'valide', 'validated_at' => DB::raw('created_at')]);
        DB::table('bon_livraison_lines')->update(['validated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('bon_livraison_lines', function (Blueprint $table) {
            $table->dropColumn('validated_at');
        });

        Schema::table('bon_livraisons', function (Blueprint $table) {
            $table->dropColumn(['status', 'validated_at']);
        });
    }
};
