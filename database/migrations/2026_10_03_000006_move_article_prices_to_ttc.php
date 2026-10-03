<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The article form now only keeps the TTC prices. Prices already typed in the HT fields
        // (there is no tax, so HT = TTC) are carried over so no existing article loses its price.
        foreach (['prix_achat', 'prix_detail', 'prix_demi_gros', 'prix_gros', 'prix_special'] as $price) {
            DB::table('articles')
                ->where($price.'_ttc', 0)
                ->where($price.'_ht', '>', 0)
                ->update([$price.'_ttc' => DB::raw($price.'_ht')]);
        }
    }

    public function down(): void
    {
        // Values copied into the TTC columns are kept: nothing to undo.
    }
};