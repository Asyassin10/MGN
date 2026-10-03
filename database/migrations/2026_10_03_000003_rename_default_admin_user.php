<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The seller printed on documents is the logged-in user's name.
        DB::table('users')->where('name', 'Administrateur Droguerie P')->update(['name' => 'Abdelatif']);
    }

    public function down(): void
    {
        DB::table('users')->where('name', 'Abdelatif')->where('email', 'admin@abd.local')->update(['name' => 'Administrateur Droguerie P']);
    }
};
