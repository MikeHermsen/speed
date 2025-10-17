<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

            $normalizedQuery = $this->normalizeValue($query);
            $tokens = $this->tokenizeQuery($query);
            $dateCandidate = $this->detectDate($query);

            $studentsQuery->where(function ($builder) use ($query, $normalizedQuery, $tokens, $dateCandidate) {
                $builder->where(function ($base) use ($query) {
                    $base->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('guardian_email', 'like', "%{$query}%")
                        ->orWhere('guardian_phone', 'like', "%{$query}%");
                });

                if ($normalizedQuery !== '') {
                    $like = "%{$normalizedQuery}%";
                    $builder->orWhereRaw($this->normalizedColumn("CONCAT(first_name, ' ', last_name)") . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn("CONCAT(last_name, ' ', first_name)") . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('first_name') . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('last_name') . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('email') . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('phone') . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('guardian_email') . ' LIKE ?', [$like])
                        ->orWhereRaw($this->normalizedColumn('guardian_phone') . ' LIKE ?', [$like]);
                }

                foreach ($tokens as $token) {
                    $tokenLike = "%{$token}%";
                    $builder->orWhere(function ($tokenBuilder) use ($tokenLike) {
                        $tokenBuilder->orWhereRaw($this->normalizedColumn('first_name') . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn('last_name') . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn("CONCAT(first_name, last_name)") . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn("CONCAT(last_name, first_name)") . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn('email') . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn('phone') . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn('guardian_email') . ' LIKE ?', [$tokenLike])
                            ->orWhereRaw($this->normalizedColumn('guardian_phone') . ' LIKE ?', [$tokenLike]);
                    });
                }

                if ($dateCandidate) {
                    $builder->orWhereDate('birth_date', $dateCandidate->toDateString());
                }
            });
        }

        $limit = $initial ? 12 : 80;

        $students = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get();

        if (! $initial) {
            $students = $students
                ->filter(fn (Student $student) => $this->matchesQuery($student, $query))
                ->sortBy(fn (Student $student) => $this->scoreStudent($student, $query))
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
            'has_guardian' => ['nullable', 'boolean'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:255'],
            'notify_student_email' => ['nullable', 'boolean'],
            'notify_guardian_email' => ['nullable', 'boolean'],
            'notify_student_phone' => ['nullable', 'boolean'],
            'notify_guardian_phone' => ['nullable', 'boolean'],
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
            'has_guardian' => array_key_exists('has_guardian', $data) ? (bool) $data['has_guardian'] : false,
            'guardian_email' => $data['guardian_email'] ?? null,
            'guardian_phone' => $data['guardian_phone'] ?? null,
            'notify_student_email' => array_key_exists('notify_student_email', $data)
                ? (bool) $data['notify_student_email']
                : true,
            'notify_guardian_email' => array_key_exists('notify_guardian_email', $data)
                ? (bool) $data['notify_guardian_email']
                : false,
            'notify_student_phone' => array_key_exists('notify_student_phone', $data)
                ? (bool) $data['notify_student_phone']
                : true,
            'notify_guardian_phone' => array_key_exists('notify_guardian_phone', $data)
                ? (bool) $data['notify_guardian_phone']
                : false,
            'package' => $data['package'] ?? null,
            'vehicle' => $data['vehicle'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        return response()->json($this->formatStudent($student), 201);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'message' => 'Leerling verwijderd.',
        ]);
    }

    protected function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'birth_date' => $student->birth_date?->toDateString(),
            'email' => $student->email,
            'phone' => $student->phone,
            'has_guardian' => $student->has_guardian,
            'guardian_email' => $student->guardian_email,
            'guardian_phone' => $student->guardian_phone,
            'notify_student_email' => $student->notify_student_email,
            'notify_guardian_email' => $student->notify_guardian_email,
            'notify_student_phone' => $student->notify_student_phone,
            'notify_guardian_phone' => $student->notify_guardian_phone,
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
            $student->guardian_email,
            $student->guardian_phone,
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

    protected function scoreStudent(Student $student, string $query): int
    {
        $normalizedQuery = $this->normalizeValue($query);

        if ($normalizedQuery === '') {
            return 1000;
        }

        $scores = [
            $this->normalizeValue($student->full_name) => 0,
            $this->normalizeValue($student->email) => 2,
            $this->normalizeValue($student->phone) => 4,
            $this->normalizeValue($student->guardian_email) => 6,
            $this->normalizeValue($student->guardian_phone) => 8,
            optional($student->birth_date)->format('dmY') => 3,
        ];

        foreach ($scores as $value => $score) {
            if ($value && str_contains($value, $normalizedQuery)) {
                return $score;
            }
        }

        return 1000;
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

    protected function tokenizeQuery(string $query): array
    {
        $prepared = preg_replace('/[\-_.@\/]+/u', ' ', $query) ?? '';

        return collect(preg_split('/\s+/u', trim($prepared)))
            ->map(fn ($token) => $this->normalizeValue($token))
            ->filter()
            ->values()
            ->all();
    }

    protected function detectDate(string $query): ?Carbon
    {
        $candidates = array_unique([
            $query,
            str_replace(['/', '.'], '-', $query),
            preg_replace('/[^0-9]/', '', $query) ?? '',
        ]);

        $formats = ['Y-m-d', 'd-m-Y', 'd-m-y', 'Ymd', 'dmY'];

        foreach ($candidates as $candidate) {
            if (! $candidate) {
                continue;
            }
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $candidate);
                    if ($date !== false) {
                        return $date;
                    }
                } catch (\Exception) {
                    // Ignore invalid formats
                }
            }
        }

        return null;
    }

    protected function normalizedColumn(string $expression): string
    {
        $wrapped = "COALESCE({$expression}, '')";

        foreach ([' ', '-', '/', '.', '+', '(', ')', '@', '_'] as $character) {
            $wrapped = "REPLACE({$wrapped}, '{$character}', '')";
        }

        return "LOWER({$wrapped})";
    }
}
