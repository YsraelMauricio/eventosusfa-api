<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $attendees = User::where('role', 'attendee')->orderBy('id')->get();
        $events = Event::orderBy('id')->get();

        foreach ($events->take(8) as $index => $event) {
            $confirmed = $event->capacity <= 2 ? 2 : 5;

            foreach ($attendees->take($confirmed) as $position => $attendee) {
                Registration::firstOrCreate(
                    ['event_id' => $event->id, 'user_id' => $attendee->id],
                    [
                        'code' => $this->code($event->id, $attendee->id),
                        'status' => RegistrationStatus::Confirmed,
                        'checked_in_at' => $event->starts_at->isPast() && $position < 3 ? $event->starts_at : null,
                    ],
                );
            }

            // Una inscripción cancelada de ejemplo para probar los filtros.
            if ($index === 5) {
                Registration::firstOrCreate(
                    ['event_id' => $event->id, 'user_id' => $attendees->last()->id],
                    [
                        'code' => $this->code($event->id, $attendees->last()->id),
                        'status' => RegistrationStatus::Cancelled,
                    ],
                );
            }
        }
    }

    private function code(int $eventId, int $userId): string
    {
        return Str::upper(Str::substr(md5("evento-{$eventId}-usuario-{$userId}"), 0, 8));
    }
}
