<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $packages = ['Starter', 'Opfris', 'Spoed'];
        $vehicles = ['Schakelauto', 'Automaat'];
        $locations = ['Amsterdam', 'Rotterdam', 'Utrecht', 'Den Haag'];

        $students = [
            ['first_name' => 'Lotte', 'last_name' => 'Visser'],
            ['first_name' => 'Hugo', 'last_name' => 'van Dijk'],
            ['first_name' => 'Sven', 'last_name' => 'Bos'],
            ['first_name' => 'Mila', 'last_name' => 'Peeters'],
            ['first_name' => 'Noor', 'last_name' => 'Smit'],
            ['first_name' => 'Thijs', 'last_name' => 'Kok'],
            ['first_name' => 'Isa', 'last_name' => 'Kuiper'],
            ['first_name' => 'Ravi', 'last_name' => 'de Wit'],
        ];

        foreach ($students as $student) {
            Student::updateOrCreate(
                ['email' => Str::slug($student['first_name'] . $student['last_name']) . '@leerling.test'],
                [
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'phone' => '06-' . random_int(10000000, 99999999),
                    'package' => Arr::random($packages),
                    'vehicle' => Arr::random($vehicles),
                    'location' => Arr::random($locations),
                ],
            );
        }
    }
}
