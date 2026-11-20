<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'career'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function isOrganizer(): bool
    {
        return $this->role === UserRole::Organizer;
    }

    public function attends(Event $event): bool
    {
        return $this->registrations()
            ->where('event_id', $event->id)
            ->where('status', 'confirmed')
            ->exists();
    }

    public function canManage(Event $event): bool
    {
        return $this->isOrganizer() && $event->organizer_id === $this->id;
    }
}
