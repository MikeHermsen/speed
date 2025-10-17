<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::instructors()->orderBy('name')->get();
        $students = Student::orderBy('last_name')->orderBy('first_name')->get();

        if ($instructors->isEmpty() || $students->isEmpty()) {
            return;
        }

        $statuses = ['les', 'proefles', 'examen', 'ziek'];
        $slots = [
            ['day' => 0, 'time' => '08:30', 'duration' => 90],
            ['day' => 0, 'time' => '11:00', 'duration' => 90],
            ['day' => 1, 'time' => '13:00', 'duration' => 90],
            ['day' => 2, 'time' => '09:00', 'duration' => 90],
            ['day' => 3, 'time' => '15:00', 'duration' => 90],
            ['day' => 4, 'time' => '10:30', 'duration' => 90],
        ];

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $studentIndex = 0;

        foreach ($instructors as $instructorIndex => $instructor) {
            foreach ($slots as $slotIndex => $slot) {
                $student = $students[$studentIndex % $students->count()];
                $studentIndex++;

                $start = $weekStart->copy()->addWeeks(1)->addDays($slot['day'])->setTimeFromTimeString($slot['time']);
                $end = $start->copy()->addMinutes($slot['duration']);

                $status = Arr::get($statuses, ($instructorIndex + $slotIndex) % count($statuses), 'les');

                Event::updateOrCreate(
                    [
                        'instructor_id' => $instructor->id,
                        'student_id' => $student->id,
                        'start_time' => $start,
                    ],
                    [
                        'end_time' => $end,
                        'status' => $status,
                        'vehicle' => $student->vehicle,
                        'package' => $student->package,
                        'email' => $student->email,
                        'phone' => $student->phone,
                        'has_guardian' => $student->has_guardian,
                        'guardian_email' => $student->guardian_email,
                        'guardian_phone' => $student->guardian_phone,
                        'notify_student_email' => $student->notify_student_email,
                        'notify_guardian_email' => $student->notify_guardian_email,
                        'notify_student_phone' => $student->notify_student_phone,
                        'notify_guardian_phone' => $student->notify_guardian_phone,
                        'location' => $student->location,
                        'description' => 'Ingepland via seeder voor demonstratie.',
                    ],
                );
            }
        }
    }
}
