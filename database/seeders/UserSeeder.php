<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organizers = [
            ['name' => 'Organizador Uno', 'email' => 'organizador@usfa.edu.bo'],
            ['name' => 'Organizadora Dos', 'email' => 'organizadora@usfa.edu.bo'],
        ];

        foreach ($organizers as $organizer) {
            User::updateOrCreate(
                ['email' => $organizer['email']],
                $organizer + [
                    'password' => 'password',
                    'role' => UserRole::Organizer,
                    'career' => 'Ingeniería de Sistemas',
                ],
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            $email = sprintf('asistente%02d@usfa.edu.bo', $i);

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => sprintf('Asistente %02d', $i),
                    'password' => 'password',
                    'role' => UserRole::Attendee,
                    'career' => 'Ingeniería de Sistemas',
                ],
            );
        }
    }
}
