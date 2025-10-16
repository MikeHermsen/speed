<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rijschool.test'],
            [
                'name' => 'Beheerder',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'instructeur1@rijschool.test'],
            [
                'name' => 'Instructeur Anja',
                'password' => Hash::make('password'),
                'role' => 'instructeur',
            ],
        );

        User::updateOrCreate(
            ['email' => 'instructeur2@rijschool.test'],
            [
                'name' => 'Instructeur Bram',
                'password' => Hash::make('password'),
                'role' => 'instructeur',
            ],
        );
    }
}
