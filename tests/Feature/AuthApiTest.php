<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nueva Persona',
            'email' => 'nueva@usfa.edu.bo',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'career' => 'Ingeniería de Sistemas',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'nueva@usfa.edu.bo')
            ->assertJsonPath('user.role', 'attendee')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role', 'career'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'nueva@usfa.edu.bo']);
    }

    public function test_registration_rejects_a_duplicate_email_and_a_weak_password(): void
    {
        User::factory()->create(['email' => 'ocupado@usfa.edu.bo']);

        $this->postJson('/api/auth/register', [
            'name' => 'Otra Persona',
            'email' => 'ocupado@usfa.edu.bo',
            'password' => 'corta',
            'password_confirmation' => 'distinta',
        ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_a_registered_user_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'login@usfa.edu.bo']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@usfa.edu.bo',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'login@usfa.edu.bo']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@usfa.edu.bo',
            'password' => 'incorrecta',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_their_profile_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'perfil@usfa.edu.bo',
            'role' => 'organizer',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'perfil@usfa.edu.bo',
            'password' => 'password',
        ]);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'organizer');

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => (int) explode('|', $token)[0],
        ]);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->postJson('/api/auth/logout')->assertUnauthorized();
        $this->getJson('/api/me/events')->assertUnauthorized();
    }
}
