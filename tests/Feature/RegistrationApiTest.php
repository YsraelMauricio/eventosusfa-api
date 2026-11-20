<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    private function eventInDays(int $days): Event
    {
        return Event::factory()->create([
            'starts_at' => now()->addDays($days),
            'ends_at' => now()->addDays($days)->addHours(2),
        ]);
    }

    public function test_an_attendee_can_register_for_a_published_event(): void
    {
        $event = $this->eventInDays(3);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/events/{$event->id}/registrations");

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.event.id', $event->id);

        $this->assertSame(8, strlen($response->json('data.code')));
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_a_person_cannot_register_twice_for_the_same_event(): void
    {
        $event = $this->eventInDays(3);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/events/{$event->id}/registrations")->assertCreated();

        $this->postJson("/api/events/{$event->id}/registrations")
            ->assertStatus(409)
            ->assertJsonPath('code', 'already_registered');
    }

    public function test_registration_is_rejected_when_the_event_is_full(): void
    {
        $event = $this->eventInDays(3);
        $event->update(['capacity' => 1]);
        Registration::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/events/{$event->id}/registrations")
            ->assertStatus(409)
            ->assertJsonPath('code', 'event_full');
    }

    public function test_registration_is_rejected_for_draft_or_past_events(): void
    {
        $draft = Event::factory()->draft()->create([
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
        ]);
        $past = Event::factory()->past()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/events/{$draft->id}/registrations")
            ->assertStatus(409)
            ->assertJsonPath('code', 'event_not_available');

        $this->postJson("/api/events/{$past->id}/registrations")
            ->assertStatus(409)
            ->assertJsonPath('code', 'event_not_available');
    }

    public function test_a_cancelled_registration_can_be_reactivated(): void
    {
        $event = $this->eventInDays(3);
        $user = User::factory()->create();
        $registration = Registration::factory()->cancelled()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'code' => 'VIEJO123',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/events/{$event->id}/registrations");

        $response->assertCreated()->assertJsonPath('data.status', 'confirmed');
        $this->assertNotSame('VIEJO123', $response->json('data.code'));
        $this->assertSame($registration->id, $response->json('data.id'));
    }

    public function test_an_attendee_can_cancel_before_the_deadline(): void
    {
        $event = $this->eventInDays(3);
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/registrations/{$registration->id}")->assertNoContent();

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancellation_is_rejected_inside_the_24_hour_window(): void
    {
        $event = Event::factory()->create([
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(14),
        ]);
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/registrations/{$registration->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'cancel_window_closed');
    }

    public function test_a_person_cannot_cancel_someone_elses_registration(): void
    {
        $registration = Registration::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/registrations/{$registration->id}")->assertForbidden();
    }

    public function test_an_attendee_can_list_their_own_registrations(): void
    {
        $user = User::factory()->create();
        Registration::factory()->count(2)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/me/registrations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'code', 'status', 'event']]]);
    }

    public function test_only_the_owner_can_list_the_registrations_of_an_event(): void
    {
        $owner = User::factory()->organizer()->create();
        $event = Event::factory()->create(['organizer_id' => $owner->id]);
        Registration::factory()->count(3)->create(['event_id' => $event->id]);

        Sanctum::actingAs($owner);
        $this->getJson("/api/events/{$event->id}/registrations")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'code', 'user' => ['id', 'name', 'email']]]]);

        Sanctum::actingAs(User::factory()->organizer()->create());
        $this->getJson("/api/events/{$event->id}/registrations")->assertForbidden();
    }

    public function test_check_in_succeeds_inside_the_window_and_is_idempotent(): void
    {
        $event = Event::factory()->create([
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
        ]);
        $owner = $event->organizer;
        $registration = Registration::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($owner);

        $first = $this->postJson("/api/events/{$event->id}/check-in", ['code' => $registration->code])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertNotNull($first->json('data.checked_in_at'));

        $this->postJson("/api/events/{$event->id}/check-in", ['code' => strtolower($registration->code)])
            ->assertOk()
            ->assertJsonPath('data.checked_in_at', $first->json('data.checked_in_at'));
    }

    public function test_check_in_is_rejected_before_the_window_opens(): void
    {
        $event = $this->eventInDays(2);
        $registration = Registration::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($event->organizer);

        $this->postJson("/api/events/{$event->id}/check-in", ['code' => $registration->code])
            ->assertStatus(409)
            ->assertJsonPath('code', 'checkin_window_closed');
    }

    public function test_check_in_with_an_unknown_code_returns_not_found(): void
    {
        $event = Event::factory()->create([
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
        ]);
        Sanctum::actingAs($event->organizer);

        $this->postJson("/api/events/{$event->id}/check-in", ['code' => 'NOEXISTE'])
            ->assertNotFound();
    }

    public function test_registration_requires_authentication(): void
    {
        $event = $this->eventInDays(3);

        $this->postJson("/api/events/{$event->id}/registrations")->assertUnauthorized();
        $this->getJson('/api/me/registrations')->assertUnauthorized();
    }
}
