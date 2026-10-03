<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $new = ['articles', 'operations', 'bons_commande', 'devis', 'groupes'];

    public function up(): void
    {
        // The "depots" access used to open the whole stock area. Keep that access for users who already have it.
        foreach (DB::table('users')->where('role', 'restricted')->whereNotNull('permissions')->get(['id', 'permissions']) as $user) {
            $permissions = json_decode($user->permissions, true) ?: [];
            $modules = $permissions['modules'] ?? [];

            if (! in_array('depots', $modules, true)) {
                continue;
            }

            $permissions['modules'] = array_values(array_unique([...$modules, ...$this->new]));
            DB::table('users')->where('id', $user->id)->update(['permissions' => json_encode($permissions)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('users')->where('role', 'restricted')->whereNotNull('permissions')->get(['id', 'permissions']) as $user) {
            $permissions = json_decode($user->permissions, true) ?: [];
            $permissions['modules'] = array_values(array_diff($permissions['modules'] ?? [], $this->new));
            DB::table('users')->where('id', $user->id)->update(['permissions' => json_encode($permissions)]);
        }
    }
};