<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+45 days');
        $endsAt = (clone $startsAt)->modify('+2 hours');

        return [
            'organizer_id' => User::factory()->organizer(),
            'category_id' => Category::factory(),
            'title' => Str::title(fake()->unique()->words(4, true)),
            'description' => fake()->paragraph(3),
            'venue' => fake()->randomElement([
                'Auditorio USFA', 'Aula 203', 'Cancha central', 'Patio cubierto', 'Laboratorio 3',
            ]),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => fake()->numberBetween(20, 100),
            'status' => EventStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Draft]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Cancelled]);
    }

    public function past(): static
    {
        return $this->state(function () {
            $startsAt = fake()->dateTimeBetween('-30 days', '-2 days');

            return [
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->modify('+2 hours'),
            ];
        });
    }
}
