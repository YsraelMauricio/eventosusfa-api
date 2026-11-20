<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory()->attendee(),
            'code' => Str::upper(Str::random(8)),
            'status' => RegistrationStatus::Confirmed,
            'checked_in_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Cancelled]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => ['checked_in_at' => now()]);
    }
}
