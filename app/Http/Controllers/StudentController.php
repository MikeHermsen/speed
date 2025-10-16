<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('query', ''));
        $initial = $request->boolean('initial');

        $studentsQuery = Student::query();

        if (! $initial) {
            if (strlen($query) < 2) {
                return response()->json([]);
            }

            $studentsQuery->where(function ($sub) use ($query) {
                $sub->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        }

        $students = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(10)
            ->get()
            ->map(fn (Student $student) => $this->formatStudent($student));

        return response()->json($students);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')],
            'phone' => ['nullable', 'string', 'max:255'],
            'package' => ['nullable', 'string', 'max:255'],
            'vehicle' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::create($data);

        return response()->json($this->formatStudent($student), 201);
    }

    protected function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'package' => $student->package,
            'vehicle' => $student->vehicle,
            'location' => $student->location,
        ];
    }
}
