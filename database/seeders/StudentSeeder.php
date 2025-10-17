<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
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

        $faker = Faker::create('nl_NL');

        foreach ($students as $student) {
            $email = Str::slug($student['first_name'] . $student['last_name']) . '@leerling.test';
            $hasGuardian = $faker->boolean(45);
            $guardianEmail = $hasGuardian ? 'voogd+' . $email : null;
            $guardianPhone = $hasGuardian ? '06' . $faker->numberBetween(10000000, 99999999) : null;
            Student::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'birth_date' => $faker->dateTimeBetween('-24 years', '-17 years')->format('Y-m-d'),
                    'phone' => '06' . $faker->numberBetween(10000000, 99999999),
                    'parent_email' => 'ouder+' . $email,
                    'parent_phone' => '06' . $faker->numberBetween(10000000, 99999999),
                    'has_guardian' => $hasGuardian,
                    'guardian_email' => $guardianEmail,
                    'guardian_phone' => $guardianPhone,
                    'notify_student_email' => true,
                    'notify_parent_email' => $faker->boolean(40),
                    'notify_guardian_email' => $hasGuardian ? $faker->boolean(35) : false,
                    'notify_student_phone' => true,
                    'notify_parent_phone' => $faker->boolean(40),
                    'notify_guardian_phone' => $hasGuardian ? $faker->boolean(35) : false,
                    'package' => Arr::random($packages),
                    'vehicle' => Arr::random($vehicles),
                    'location' => Arr::random($locations),
                ],
            );
        }
    }
}
