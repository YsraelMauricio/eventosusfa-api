<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'capacity' => $this->capacity,
            'available_seats' => $this->availableSeats(),
            'is_full' => $this->isFull(),
            'status' => $this->status->value,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'organizer' => $this->whenLoaded('organizer', fn () => [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
            ]),
            'registrations_count' => (int) ($this->registrations_count ?? $this->confirmedRegistrations()->count()),
        ];
    }
}
