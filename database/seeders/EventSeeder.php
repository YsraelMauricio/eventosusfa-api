<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = User::where('role', 'organizer')->orderBy('id')->get();
        $categories = Category::pluck('id', 'slug');
        $base = Carbon::now()->startOfDay();

        $events = [
            ['Taller de Laravel desde cero', 'taller', 'Laboratorio 3', -28, 30, EventStatus::Published],
            ['Conferencia de inteligencia artificial', 'conferencia', 'Auditorio USFA', -21, 80, EventStatus::Published],
            ['Torneo relámpago de fútbol', 'deportivo', 'Cancha central', -14, 40, EventStatus::Published],
            ['Feria de emprendimientos', 'feria', 'Patio cubierto', -7, 60, EventStatus::Published],
            ['Concierto de cierre de semestre', 'cultural', 'Auditorio USFA', 50, 100, EventStatus::Cancelled],
            ['Taller de Git y GitHub', 'taller', 'Laboratorio 3', 2, 2, EventStatus::Published],
            ['Charla de ciberseguridad', 'conferencia', 'Auditorio USFA', 3, 70, EventStatus::Published],
            ['Competencia de robótica', 'competencia', 'Patio cubierto', 4, 30, EventStatus::Published],
            ['Taller de Vue 3', 'taller', 'Laboratorio 3', 5, 25, EventStatus::Published],
            ['Conferencia de arquitectura de software', 'conferencia', 'Auditorio USFA', 6, 90, EventStatus::Published],
            ['Torneo de básquetbol', 'deportivo', 'Cancha central', 7, 24, EventStatus::Published],
            ['Noche cultural', 'cultural', 'Auditorio USFA', 8, 120, EventStatus::Published],
            ['Feria de ciencia y tecnología', 'feria', 'Patio cubierto', 9, 200, EventStatus::Published],
            ['Competencia de programación', 'competencia', 'Laboratorio 3', 10, 40, EventStatus::Published],
            ['Taller de PostgreSQL', 'taller', 'Laboratorio 3', 11, 30, EventStatus::Published],
            ['Conferencia de desarrollo web', 'conferencia', 'Auditorio USFA', 12, 80, EventStatus::Published],
            ['Clase abierta de Docker', 'taller', 'Laboratorio 3', 13, 35, EventStatus::Published],
            ['Torneo de ajedrez', 'competencia', 'Aula 203', 14, 32, EventStatus::Published],
            ['Festival de música universitaria', 'cultural', 'Patio cubierto', 15, 150, EventStatus::Published],
            ['Feria de salud', 'feria', 'Patio cubierto', 16, 100, EventStatus::Published],
            ['Taller de despliegue en la nube', 'taller', 'Laboratorio 3', 17, 40, EventStatus::Published],
            ['Conferencia de datos abiertos', 'conferencia', 'Auditorio USFA', 18, 75, EventStatus::Published],
            ['Competencia de diseño UX', 'competencia', 'Aula 203', 19, 45, EventStatus::Published],
            ['Encuentro de egresados', 'cultural', 'Auditorio USFA', 20, 120, EventStatus::Published],
        ];

        foreach ($events as $index => [$title, $slug, $venue, $dayOffset, $capacity, $status]) {
            $startsAt = $base->copy()->addDays($dayOffset)->setTime(10, 0);

            Event::updateOrCreate(
                ['title' => $title],
                [
                    'organizer_id' => $organizers[$index % $organizers->count()]->id,
                    'category_id' => $categories[$slug],
                    'description' => 'Actividad organizada por la comunidad USFA. '.$title.'.',
                    'venue' => $venue,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHours(2),
                    'capacity' => $capacity,
                    'status' => $status,
                ],
            );
        }
    }
}
