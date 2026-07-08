<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_routes_require_authentication(): void
    {
        $this->getJson('/api/api-keys')->assertUnauthorized();
    }

    public function test_a_user_can_create_an_api_key_and_sees_the_plaintext_once(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/api-keys', [
            'name' => 'CI Deploy Key',
            'scopes' => ['read', 'write'],
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'key_prefix', 'scopes'], 'plaintext_key']);

        $plaintext = $response->json('plaintext_key');
        $this->assertNotEmpty($plaintext);

        // Only the hash is ever stored, never the plaintext.
        $this->assertDatabaseHas('api_keys', [
            'name' => 'CI Deploy Key',
            'key_hash' => hash('sha256', $plaintext),
        ]);
        $this->assertDatabaseMissing('api_keys', ['key_hash' => $plaintext]);
    }

    public function test_a_user_only_sees_their_own_api_keys(): void
    {
        $user = User::factory()->create();
        ApiKey::factory()->count(2)->for($user)->create();
        ApiKey::factory()->count(3)->create();

        $this->actingAs($user)
            ->getJson('/api/api-keys')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_user_cannot_view_another_users_api_key(): void
    {
        $user = User::factory()->create();
        $otherKey = ApiKey::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/api-keys/{$otherKey->id}")
            ->assertNotFound();
    }

    public function test_a_user_can_update_their_api_key(): void
    {
        $user = User::factory()->create();
        $key = ApiKey::factory()->for($user)->create(['name' => 'Old']);

        $this->actingAs($user)
            ->putJson("/api/api-keys/{$key->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('api_keys', ['id' => $key->id, 'name' => 'New Name']);
    }

    public function test_a_user_cannot_update_another_users_api_key(): void
    {
        $user = User::factory()->create();
        $otherKey = ApiKey::factory()->create();

        $this->actingAs($user)
            ->putJson("/api/api-keys/{$otherKey->id}", ['name' => 'Hijacked'])
            ->assertNotFound();
    }

    public function test_a_user_can_revoke_their_api_key(): void
    {
        $user = User::factory()->create();
        $key = ApiKey::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson("/api/api-keys/{$key->id}")
            ->assertOk();

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
    }

    public function test_creating_a_key_rejects_invalid_scopes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/api-keys', ['name' => 'Bad', 'scopes' => ['superadmin']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scopes.0');
    }
}
