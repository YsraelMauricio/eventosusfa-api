<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => UserRole::Attendee,
            'career' => 'Ingeniería de Sistemas',
            'remember_token' => Str::random(10),
        ];
    }

    public function organizer(): static
    {
        return $this->state(fn () => ['role' => UserRole::Organizer]);
    }

    public function attendee(): static
    {
        return $this->state(fn () => ['role' => UserRole::Attendee]);
    }
}
