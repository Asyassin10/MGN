<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $decimals = [
        'commission_vendeur', 'stock_minimum', 'poids',
        'prix_achat_ht', 'prix_achat_ttc',
        'marge_detail', 'marge_demi_gros', 'marge_gros', 'marge_special',
        'prix_detail_ht', 'prix_detail_ttc', 'prix_demi_gros_ht', 'prix_demi_gros_ttc',
        'prix_gros_ht', 'prix_gros_ttc', 'prix_special_ht', 'prix_special_ttc',
        'prix_min', 'prix_max',
    ];

    public function up(): void
    {
        Schema::create('article_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $generalId = DB::table('article_groups')->insertGetId(['name' => 'Général', 'created_at' => now(), 'updated_at' => now()]);

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('name')->constrained('article_groups')->nullOnDelete();
            $table->string('nom_fournisseur')->nullable()->after('group_id');
            $table->string('unite', 5)->default('U')->after('nom_fournisseur');
            foreach ($this->decimals as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }
        });

        DB::table('articles')->update(['group_id' => $generalId]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['nom_fournisseur', 'unite', ...$this->decimals]);
        });

        Schema::dropIfExists('article_groups');
    }
};
