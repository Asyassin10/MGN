<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_any_admin_route(): void
    {
        $user = User::factory()->create(['role' => 'restricted', 'permissions' => ['modules' => ['clients'], 'delete' => []]]);

        $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('activity-history.index'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.database-backup.download'))->assertForbidden();
    }

    public function test_user_management_only_lists_and_manages_restricted_accounts(): void
    {
        $admin = User::factory()->create(['name' => 'Admin account', 'role' => 'admin']);
        $restricted = User::factory()->create(['name' => 'Restricted account', 'role' => 'restricted']);

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();
        preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $page = json_decode($matches[1] ?? '', true);
        $this->assertSame(['Restricted account'], collect($page['props']['users'] ?? [])->pluck('name')->all());

        $this->actingAs($admin)->patch(route('users.update', $admin), [
            'name' => 'Changed admin',
            'pin' => '123456',
        ])->assertNotFound();

        $this->actingAs($admin)->patch(route('users.update', $restricted), [
            'name' => 'Changed restricted',
            'pin' => '123456',
        ])->assertRedirect();
    }

    public function test_history_shows_only_restricted_user_activity_in_readable_french(): void
    {
        $admin = User::factory()->create(['name' => 'Administrateur', 'role' => 'admin']);
        $restricted = User::factory()->create(['name' => 'Restricted user', 'role' => 'restricted']);

        $this->log($admin, 'Admin action', ['montant' => '100.00'], ['montant' => '200.00']);
        $this->log($restricted, 'Client A', ['montant' => '100.00', 'statut' => 'en_cours'], ['montant' => '200.00', 'statut' => 'en_caisse']);

        $response = $this->actingAs($admin)->get(route('activity-history.index'));

        $response->assertOk()
            ->assertSee('Restricted user')
            ->assertDontSee('Admin action')
            ->assertSee('Modification')
            ->assertSee('Montant')
            ->assertSee('100,00 DH')
            ->assertSee('En caisse');
    }

    public function test_history_filters_by_precise_date_and_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $restricted = User::factory()->create(['role' => 'restricted']);
        $old = $this->log($restricted, 'Old record', null, ['nom' => 'Old record']);
        $recent = $this->log($restricted, 'Recent record', null, ['nom' => 'Recent record']);
        // Dates are stored in UTC; the filter represents Casablanca local time.
        $old->forceFill(['created_at' => '2026-08-20 08:00:00', 'updated_at' => '2026-08-20 08:00:00'])->saveQuietly();
        $recent->forceFill(['created_at' => '2026-08-20 10:00:00', 'updated_at' => '2026-08-20 10:00:00'])->saveQuietly();

        $this->actingAs($admin)->get(route('activity-history.index', ['from' => '2026-08-20T10:00', 'to' => '2026-08-20T12:00']))
            ->assertOk()
            ->assertSee('Recent record')
            ->assertDontSee('Old record');
    }

    public function test_prune_command_keeps_only_the_last_thirty_days(): void
    {
        $user = User::factory()->create(['role' => 'restricted']);
        $old = $this->log($user, 'Ancien', null, ['nom' => 'Ancien']);
        $recent = $this->log($user, 'Récent', null, ['nom' => 'Récent']);
        $old->forceFill(['created_at' => now()->subDays(31)])->saveQuietly();
        $recent->forceFill(['created_at' => now()->subDays(29)])->saveQuietly();

        $this->artisan('activity-logs:prune')->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recent->id]);
    }

    private function log(User $user, string $label, ?array $before, ?array $after): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'module' => 'Clients',
            'subject_type' => 'Client',
            'subject_id' => random_int(1, 100000),
            'subject_label' => $label,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
