<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_list_is_public_and_returns_every_category(): void
    {
        Category::factory()->create(['name' => 'Taller', 'slug' => 'taller']);
        Category::factory()->create(['name' => 'Conferencia', 'slug' => 'conferencia']);

        $response = $this->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'color']],
            ]);
    }

    public function test_category_list_is_sorted_by_name(): void
    {
        Category::factory()->create(['name' => 'Zumba', 'slug' => 'zumba']);
        Category::factory()->create(['name' => 'Ajedrez', 'slug' => 'ajedrez']);

        $response = $this->getJson('/api/categories');

        $this->assertSame('Ajedrez', $response->json('data.0.name'));
        $this->assertSame('Zumba', $response->json('data.1.name'));
    }
}
