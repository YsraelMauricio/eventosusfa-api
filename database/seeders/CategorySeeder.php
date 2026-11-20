<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Taller', 'color' => '#0D6EFD'],
            ['name' => 'Conferencia', 'color' => '#6F42C1'],
            ['name' => 'Deportivo', 'color' => '#198754'],
            ['name' => 'Cultural', 'color' => '#FD7E14'],
            ['name' => 'Feria', 'color' => '#D63384'],
            ['name' => 'Competencia', 'color' => '#DC3545'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'color' => $category['color'],
                ],
            );
        }
    }
}
