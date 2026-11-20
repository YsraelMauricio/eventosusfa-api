<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_relations_and_casts_are_configured(): void
    {
        $event = Event::factory()->create(['starts_at' => now()->addDays(3)]);

        $this->assertInstanceOf(EventStatus::class, $event->status);
        $this->assertInstanceOf(User::class, $event->organizer);
        $this->assertInstanceOf(Category::class, $event->category);
        $this->assertTrue($event->starts_at->isFuture());
    }

    public function test_event_scopes_filter_by_status_and_date(): void
    {
        Event::factory()->create(['starts_at' => now()->addDays(2)]);
        Event::factory()->draft()->create(['starts_at' => now()->addDays(3)]);
        Event::factory()->cancelled()->create(['starts_at' => now()->addDays(4)]);
        Event::factory()->past()->create();

        $this->assertSame(1, Event::published()->upcoming()->count());
    }

    public function test_available_seats_ignores_cancelled_registrations(): void
    {
        $event = Event::factory()->create(['capacity' => 2]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();

        Registration::factory()->create(['event_id' => $event->id, 'user_id' => $first->id]);
        Registration::factory()->create(['event_id' => $event->id, 'user_id' => $second->id]);
        Registration::factory()->cancelled()->create(['event_id' => $event->id, 'user_id' => $third->id]);

        $this->assertSame(0, $event->availableSeats());
        $this->assertTrue($event->isFull());
    }

    public function test_user_knows_whether_they_attend_an_event(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->attends($event));

        Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RegistrationStatus::Confirmed,
        ]);

        $this->assertTrue($user->attends($event));
    }
}
