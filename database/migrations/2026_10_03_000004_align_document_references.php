<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bons de commande (table "devis") created before the rename carry a DV- prefix: they become BC-.
        foreach (DB::table('devis')->where('reference', 'like', 'DV-%')->get(['id', 'reference']) as $row) {
            DB::table('devis')->where('id', $row->id)->update(['reference' => 'BC-'.substr($row->reference, 3)]);
        }

        // Devis (table "bon_livraisons") created before the rename carry a BL- prefix: they become DV-.
        foreach (DB::table('bon_livraisons')->where('reference', 'like', 'BL-%')->get(['id', 'reference']) as $row) {
            DB::table('bon_livraisons')->where('id', $row->id)->update(['reference' => 'DV-'.substr($row->reference, 3)]);
        }

        // Keep the stock-operation notes consistent with the new names, using the exact links to each document.
        $orders = DB::table('devis_lines')
            ->join('devis', 'devis.id', '=', 'devis_lines.devis_id')
            ->whereNotNull('devis_lines.operation_id')
            ->get(['devis_lines.operation_id as operation_id', 'devis.reference as reference']);

        foreach ($orders as $row) {
            DB::table('operations')->where('id', $row->operation_id)->update(['note' => 'Bon de commande '.$row->reference]);
        }

        $quotes = DB::table('bon_livraison_lines')
            ->join('bon_livraisons', 'bon_livraisons.id', '=', 'bon_livraison_lines.bon_livraison_id')
            ->whereNotNull('bon_livraison_lines.operation_id')
            ->get(['bon_livraison_lines.operation_id as operation_id', 'bon_livraisons.reference as reference']);

        foreach ($quotes as $row) {
            DB::table('operations')->where('id', $row->operation_id)->update(['note' => 'Devis '.$row->reference]);
        }
    }

    public function down(): void
    {
        // Renamed references are not restored: the old and new prefixes cannot be told apart reliably.
    }
};
