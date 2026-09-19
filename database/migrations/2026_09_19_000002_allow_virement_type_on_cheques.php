<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cheque_clients DROP CONSTRAINT IF EXISTS cheque_clients_type_check');
        DB::statement("ALTER TABLE cheque_clients ADD CONSTRAINT cheque_clients_type_check CHECK (type IN ('cheque', 'effet', 'virement'))");

        DB::statement('ALTER TABLE fournisseur_cheques DROP CONSTRAINT IF EXISTS fournisseur_cheques_type_check');
        DB::statement("ALTER TABLE fournisseur_cheques ADD CONSTRAINT fournisseur_cheques_type_check CHECK (type IN ('cheque', 'effet', 'virement'))");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cheque_clients DROP CONSTRAINT IF EXISTS cheque_clients_type_check');
        DB::statement("ALTER TABLE cheque_clients ADD CONSTRAINT cheque_clients_type_check CHECK (type IN ('cheque', 'effet'))");

        DB::statement('ALTER TABLE fournisseur_cheques DROP CONSTRAINT IF EXISTS fournisseur_cheques_type_check');
        DB::statement("ALTER TABLE fournisseur_cheques ADD CONSTRAINT fournisseur_cheques_type_check CHECK (type IN ('cheque', 'effet'))");
    }
};
