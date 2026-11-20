<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizerImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_read_attendance_statistics(): void
    {
        $event = Event::factory()->create();
        Registration::factory()->for($event)->checkedIn()->create();
        Registration::factory()->for($event)->create();
        Sanctum::actingAs($event->organizer);

        $this->getJson("/api/events/{$event->id}/stats")
            ->assertOk()
            ->assertJsonPath('data.confirmed_count', 2)
            ->assertJsonPath('data.checked_in_count', 1)
            ->assertJsonPath('data.attendance_percent', 50);
    }

    public function test_only_event_owner_can_read_statistics_and_export_csv(): void
    {
        $event = Event::factory()->create();
        $otherOrganizer = User::factory()->organizer()->create();
        Sanctum::actingAs($otherOrganizer);

        $this->getJson("/api/events/{$event->id}/stats")->assertForbidden();
        $this->get("/api/events/{$event->id}/registrations.csv")->assertForbidden();
    }

    public function test_owner_can_export_registration_csv(): void
    {
        $event = Event::factory()->create();
        $registration = Registration::factory()->for($event)->create();
        Sanctum::actingAs($event->organizer);

        $response = $this->get("/api/events/{$event->id}/registrations.csv");

        $response->assertOk();
        $this->assertStringContainsString('Código,Nombre,Correo,Estado,Check-in', $response->streamedContent());
        $this->assertStringContainsString($registration->code, $response->streamedContent());
    }
}
