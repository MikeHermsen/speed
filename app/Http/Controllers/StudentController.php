<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->string('query')->trim();

        $students = Student::query()
            ->when($query->isNotEmpty(), function ($builder) use ($query) {
                $builder->where(function ($sub) use ($query) {
                    $sub->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'package' => $student->package,
                'vehicle' => $student->vehicle,
                'location' => $student->location,
            ]);

        return response()->json($students);
    }
}
