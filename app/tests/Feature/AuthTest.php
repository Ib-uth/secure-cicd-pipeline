<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'Sup3rSecret!',
            'password_confirmation' => 'Sup3rSecret!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'password' => 'Sup3rSecret!',
            'password_confirmation' => 'Sup3rSecret!',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'grace@example.com',
            'password' => Hash::make('Sup3rSecret!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'grace@example.com',
            'password' => 'Sup3rSecret!',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'grace@example.com',
            'password' => Hash::make('Sup3rSecret!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'grace@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_the_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_an_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
