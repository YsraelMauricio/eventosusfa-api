<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_has_the_expected_volume(): void
    {
        $this->seed();

        $this->assertSame(6, Category::count());
        $this->assertSame(12, User::count());
        $this->assertSame(24, Event::count());
        $this->assertSame(4, Event::where('starts_at', '<', now())->count());
        $this->assertSame(23, Event::published()->count());
        $this->assertSame(1, Event::where('status', 'cancelled')->count());
    }

    public function test_demo_data_includes_a_full_event(): void
    {
        $this->seed();

        $full = Event::where('capacity', 2)->firstOrFail();

        $this->assertTrue($full->isFull());
        $this->assertSame(0, $full->availableSeats());
    }
}
