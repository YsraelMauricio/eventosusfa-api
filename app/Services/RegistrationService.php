<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function register(Event $event, User $user): Registration
    {
        return DB::transaction(function () use ($event, $user) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($event->status !== EventStatus::Published || $event->starts_at->isPast()) {
                throw BusinessRuleException::eventNotAvailable();
            }

            $existing = Registration::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing?->status === RegistrationStatus::Confirmed) {
                throw BusinessRuleException::alreadyRegistered();
            }

            if ($event->confirmedRegistrations()->count() >= $event->capacity) {
                throw BusinessRuleException::eventFull();
            }

            if ($existing) {
                $existing->update([
                    'status' => RegistrationStatus::Confirmed,
                    'code' => $this->newCode(),
                    'checked_in_at' => null,
                ]);

                return $existing->refresh();
            }

            return Registration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'code' => $this->newCode(),
                'status' => RegistrationStatus::Confirmed,
            ]);
        });
    }

    public function cancel(Registration $registration): void
    {
        if ($registration->status === RegistrationStatus::Cancelled) {
            return;
        }

        if (now()->gte($registration->event->starts_at->copy()->subHours(24))) {
            throw BusinessRuleException::cancelWindowClosed();
        }

        $registration->update(['status' => RegistrationStatus::Cancelled]);
    }

    public function checkIn(Event $event, string $code): Registration
    {
        $registration = $event->registrations()
            ->where('code', Str::upper(trim($code)))
            ->where('status', RegistrationStatus::Confirmed)
            ->first();

        abort_if($registration === null, 404, 'No hay una inscripción con ese código en este evento.');

        $windowStart = $event->starts_at->copy()->subHours(2);

        if (now()->lt($windowStart) || now()->gt($event->ends_at)) {
            throw BusinessRuleException::checkinWindowClosed();
        }

        if (! $registration->checked_in_at) {
            $registration->update(['checked_in_at' => now()]);
        }

        return $registration->refresh();
    }

    private function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Registration::where('code', $code)->exists());

        return $code;
    }
}
