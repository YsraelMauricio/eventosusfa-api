<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $user->canManage($event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->canManage($event);
    }

    public function viewRegistrations(User $user, Event $event): bool
    {
        return $user->canManage($event);
    }

    public function checkIn(User $user, Event $event): bool
    {
        return $user->canManage($event);
    }
}
