<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devis_lines', function (Blueprint $table) {
            $table->foreignId('depot_id')->nullable()->after('article_id')->constrained('depots')->nullOnDelete();
            $table->foreignId('operation_id')->nullable()->after('depot_id')->constrained('operations')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('devis_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operation_id');
            $table->dropConstrainedForeignId('depot_id');
            $table->dropColumn('validated_at');
        });
    }
};
