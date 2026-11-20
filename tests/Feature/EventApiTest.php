<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    private function organizer(): User
    {
        return User::factory()->organizer()->create();
    }

    public function test_event_list_only_returns_published_upcoming_events(): void
    {
        Event::factory()->create(['starts_at' => now()->addDays(2)]);
        Event::factory()->create(['starts_at' => now()->addDays(3)]);
        Event::factory()->draft()->create(['starts_at' => now()->addDays(4)]);
        Event::factory()->past()->create();

        $response = $this->getJson('/api/events');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_event_list_supports_text_category_date_and_sort_filters(): void
    {
        $category = Category::factory()->create(['slug' => 'taller']);
        Event::factory()->create([
            'title' => 'Taller de Laravel',
            'category_id' => $category->id,
            'starts_at' => now()->addDays(5),
        ]);
        Event::factory()->create([
            'title' => 'Conferencia de IA',
            'starts_at' => now()->addDays(40),
        ]);

        $this->getJson('/api/events?q=LARAVEL')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/events?category=taller')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/events?from='.now()->addDays(30)->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->getJson('/api/events?sort=-starts_at')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Conferencia de IA');
    }

    public function test_event_list_rejects_an_invalid_page_size(): void
    {
        $this->getJson('/api/events?per_page=100')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_event_detail_is_public_for_published_events(): void
    {
        $event = Event::factory()->create();

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.status', EventStatus::Published->value);
    }

    public function test_event_detail_hides_drafts_from_visitors(): void
    {
        $event = Event::factory()->draft()->create();

        $this->getJson("/api/events/{$event->id}")->assertNotFound();
    }

    public function test_only_organizers_can_create_events(): void
    {
        $payload = [
            'title' => 'Nuevo taller',
            'description' => 'Una descripción suficientemente larga para el evento.',
            'venue' => 'Laboratorio 1',
            'category_id' => Category::factory()->create()->id,
            'starts_at' => now()->addDays(5)->toIso8601String(),
            'ends_at' => now()->addDays(5)->addHours(2)->toIso8601String(),
            'capacity' => 30,
        ];

        $this->postJson('/api/events', $payload)->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/events', $payload)->assertForbidden();

        Sanctum::actingAs($this->organizer());
        $this->postJson('/api/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Nuevo taller');
    }

    public function test_event_creation_validates_dates_and_capacity(): void
    {
        Sanctum::actingAs($this->organizer());

        $this->postJson('/api/events', [
            'title' => 'Evento inválido',
            'description' => 'Descripción con longitud suficiente para pasar.',
            'venue' => 'Aula',
            'category_id' => 999,
            'starts_at' => now()->subDay()->toIso8601String(),
            'ends_at' => now()->subDays(2)->toIso8601String(),
            'capacity' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors([
            'category_id', 'starts_at', 'ends_at', 'capacity',
        ]);
    }

    public function test_owner_can_update_an_event(): void
    {
        $organizer = $this->organizer();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        Sanctum::actingAs($organizer);

        $this->putJson("/api/events/{$event->id}", ['title' => 'Título actualizado'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título actualizado');
    }

    public function test_another_organizer_cannot_update_the_event(): void
    {
        $event = Event::factory()->create();
        Sanctum::actingAs($this->organizer());

        $this->putJson("/api/events/{$event->id}", ['title' => 'Intruso'])
            ->assertForbidden();
    }

    public function test_another_organizer_cannot_delete_the_event(): void
    {
        $event = Event::factory()->create();
        Sanctum::actingAs($this->organizer());

        $this->deleteJson("/api/events/{$event->id}")->assertForbidden();
    }

    public function test_capacity_cannot_be_lower_than_confirmed_registrations(): void
    {
        $organizer = $this->organizer();
        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'capacity' => 10,
        ]);
        Registration::factory()->count(2)->create(['event_id' => $event->id]);
        Sanctum::actingAs($organizer);

        $this->putJson("/api/events/{$event->id}", ['capacity' => 1])
            ->assertStatus(409)
            ->assertJsonPath('code', 'capacity_below_registered');
    }

    public function test_event_with_registrations_cannot_be_deleted(): void
    {
        $organizer = $this->organizer();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        Registration::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($organizer);

        $this->deleteJson("/api/events/{$event->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'event_has_registrations');
    }

    public function test_owner_can_delete_an_event_without_registrations(): void
    {
        $organizer = $this->organizer();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        Sanctum::actingAs($organizer);

        $this->deleteJson("/api/events/{$event->id}")->assertNoContent();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_organizer_lists_only_their_own_events(): void
    {
        $organizer = $this->organizer();
        Event::factory()->create(['organizer_id' => $organizer->id]);
        Event::factory()->create();
        Sanctum::actingAs($organizer);

        $this->getJson('/api/me/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.organizer.id', $organizer->id);
    }
}
