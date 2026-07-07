<?php

namespace Tests\Feature;

use App\Models\Bonsai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonsaiMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_load_dashboard(): void
    {
        User::factory()->create([
            'email' => 'demo@bonsaigest.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'demo@bonsaigest.test',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.email', 'demo@bonsaigest.test');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'total_bonsais',
                'bonsais',
                'latest_events',
                'upcoming_tasks',
                'latest_images',
            ]);
    }

    public function test_user_can_create_bonsai_and_cannot_see_another_users_tree(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        Bonsai::create([
            'user_id' => $otherUser->id,
            'name' => 'Hidden pine',
            'species' => 'Pinus thunbergii',
        ]);

        $this->actingAs($owner)
            ->postJson('/api/bonsais', [
                'name' => 'Kumo',
                'species' => 'Juniperus chinensis',
                'origin' => 'Compra',
                'style' => 'Moyogi',
                'water_level' => 'Media',
                'location' => 'Exterior',
            ])->assertCreated()
            ->assertJsonPath('bonsai.name', 'Kumo');

        $this->getJson('/api/bonsais')
            ->assertOk()
            ->assertJsonCount(1, 'bonsais')
            ->assertJsonPath('bonsais.0.name', 'Kumo');
    }
}
