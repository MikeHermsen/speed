<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('parent_email', 'like', "%{$query}%")
                    ->orWhere('parent_phone', 'like', "%{$query}%");
            });
        }

        $limit = $initial ? 12 : 50;

        $students = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get();

        if (! $initial) {
            $students = $students
                ->filter(fn (Student $student) => $this->matchesQuery($student, $query))
                ->values();
        }

        $students = $students->take(12)->map(fn (Student $student) => $this->formatStudent($student));

        return response()->json($students);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')],
            'phone' => ['nullable', 'string', 'max:255'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:255'],
            'notify_student_email' => ['nullable', 'boolean'],
            'notify_parent_email' => ['nullable', 'boolean'],
            'notify_student_phone' => ['nullable', 'boolean'],
            'notify_parent_phone' => ['nullable', 'boolean'],
            'package' => ['nullable', 'string', 'max:255'],
            'vehicle' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'parent_email' => $data['parent_email'] ?? null,
            'parent_phone' => $data['parent_phone'] ?? null,
            'notify_student_email' => array_key_exists('notify_student_email', $data)
                ? (bool) $data['notify_student_email']
                : true,
            'notify_parent_email' => array_key_exists('notify_parent_email', $data)
                ? (bool) $data['notify_parent_email']
                : false,
            'notify_student_phone' => array_key_exists('notify_student_phone', $data)
                ? (bool) $data['notify_student_phone']
                : true,
            'notify_parent_phone' => array_key_exists('notify_parent_phone', $data)
                ? (bool) $data['notify_parent_phone']
                : false,
            'package' => $data['package'] ?? null,
            'vehicle' => $data['vehicle'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        return response()->json($this->formatStudent($student), 201);
    }

    protected function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'birth_date' => $student->birth_date?->toDateString(),
            'email' => $student->email,
            'phone' => $student->phone,
            'parent_email' => $student->parent_email,
            'parent_phone' => $student->parent_phone,
            'notify_student_email' => $student->notify_student_email,
            'notify_parent_email' => $student->notify_parent_email,
            'notify_student_phone' => $student->notify_student_phone,
            'notify_parent_phone' => $student->notify_parent_phone,
            'package' => $student->package,
            'vehicle' => $student->vehicle,
            'location' => $student->location,
        ];
    }

    protected function matchesQuery(Student $student, string $query): bool
    {
        $normalizedQuery = $this->normalizeValue($query);

        if ($normalizedQuery === '') {
            return false;
        }

        $fields = [
            $student->first_name,
            $student->last_name,
            $student->full_name,
            trim($student->last_name . ' ' . $student->first_name),
            $student->email,
            $student->phone,
            $student->parent_email,
            $student->parent_phone,
            $student->birth_date?->format('Y-m-d'),
            $student->birth_date?->format('d-m-Y'),
            $student->birth_date?->format('dmY'),
            $student->birth_date?->format('Ymd'),
        ];

        $normalizedFields = collect($fields)
            ->filter()
            ->map(fn ($value) => $this->normalizeValue($value));

        $concatenated = $this->normalizeValue(implode(' ', $fields));

        if ($concatenated !== '' && str_contains($concatenated, $normalizedQuery)) {
            return true;
        }

        $tokens = collect(preg_split('/\s+/', trim($query)))
            ->filter()
            ->map(fn ($token) => $this->normalizeValue($token))
            ->filter();

        if ($tokens->isEmpty()) {
            return false;
        }

        return $tokens->every(function ($token) use ($normalizedFields) {
            return $normalizedFields->contains(fn ($field) => str_contains($field, $token));
        });
    }

    protected function normalizeValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^\p{L}0-9]/u', '')
            ->value();
    }
}
