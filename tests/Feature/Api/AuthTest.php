<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_a_token_and_rejects_duplicate_email(): void
    {
        $payload = [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'no_hp' => '081234567890',
            'nim' => '2610036',
            'prodi' => 'Sistem Informasi',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];

        $this->postJson('/api/register', $payload)
            ->assertCreated()
            ->assertJsonPath('user.email', $payload['email'])
            ->assertJsonPath('user.no_hp', $payload['no_hp'])
            ->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_token_can_access_profile_and_logout(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('user.email', 'budi@example.com');

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }
}
