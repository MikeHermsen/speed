<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $start = $request->date('start');
        $end = $request->date('end');

        $eventsQuery = Event::with(['student', 'instructor'])
            ->when($start instanceof \DateTimeInterface, fn ($query) => $query->where('end_time', '>=', $start))
            ->when($end instanceof \DateTimeInterface, fn ($query) => $query->where('start_time', '<=', $end));

        if ($user->isInstructor()) {
            $eventsQuery->where('instructor_id', $user->id);
        } else {
            $instructorIds = collect($request->input('instructor_ids', []))
                ->filter()
                ->map(fn ($value) => (int) $value)
                ->values();

            if ($instructorIds->isNotEmpty()) {
                $eventsQuery->whereIn('instructor_id', $instructorIds->all());
            }
        }

        $statuses = collect($request->input('statuses', []))
            ->filter(fn ($status) => in_array($status, ['examen', 'les', 'proefles', 'ziek'], true))
            ->values();

        if ($statuses->isNotEmpty()) {
            $eventsQuery->whereIn('status', $statuses->all());
        }

        $events = $eventsQuery
            ->orderBy('start_time')
            ->get()
            ->map(fn ($event) => $this->formatEvent($event));

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'status' => ['required', 'in:examen,les,proefles,ziek'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'vehicle' => ['nullable', 'string'],
            'package' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'has_guardian' => ['nullable', 'boolean'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_phone' => ['nullable', 'string'],
            'notify_student_email' => ['nullable', 'boolean'],
            'notify_guardian_email' => ['nullable', 'boolean'],
            'notify_student_phone' => ['nullable', 'boolean'],
            'notify_guardian_phone' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'student_birth_date' => ['nullable', 'date'],
        ];

        if ($user->isAdmin()) {
            $rules['instructor_id'] = ['required', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        $student = Student::findOrFail($data['student_id']);

        if (array_key_exists('student_birth_date', $data)) {
            $student->birth_date = $data['student_birth_date'] ?: null;
            if ($student->isDirty('birth_date')) {
                $student->save();
            }
        }

        $instructorId = $user->isAdmin() ? $data['instructor_id'] : $user->id;

        $event = Event::create([
            'instructor_id' => $instructorId,
            'student_id' => $student->id,
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'vehicle' => $data['vehicle'] ?? $student->vehicle,
            'package' => $data['package'] ?? $student->package,
            'email' => array_key_exists('email', $data) ? $data['email'] : $student->email,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $student->phone,
            'has_guardian' => array_key_exists('has_guardian', $data)
                ? (bool) $data['has_guardian']
                : (bool) $student->has_guardian,
            'guardian_email' => array_key_exists('guardian_email', $data)
                ? $data['guardian_email']
                : $student->guardian_email,
            'guardian_phone' => array_key_exists('guardian_phone', $data)
                ? $data['guardian_phone']
                : $student->guardian_phone,
            'notify_student_email' => array_key_exists('notify_student_email', $data)
                ? (bool) $data['notify_student_email']
                : (bool) $student->notify_student_email,
            'notify_guardian_email' => array_key_exists('notify_guardian_email', $data)
                ? (bool) $data['notify_guardian_email']
                : (bool) $student->notify_guardian_email,
            'notify_student_phone' => array_key_exists('notify_student_phone', $data)
                ? (bool) $data['notify_student_phone']
                : (bool) $student->notify_student_phone,
            'notify_guardian_phone' => array_key_exists('notify_guardian_phone', $data)
                ? (bool) $data['notify_guardian_phone']
                : (bool) $student->notify_guardian_phone,
            'location' => $data['location'] ?? $student->location,
            'description' => $data['description'] ?? null,
        ]);

        $event->load(['student', 'instructor']);

        return response()->json($this->formatEvent($event), 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($user->isInstructor() && $event->instructor_id !== $user->id) {
            abort(403);
        }

        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'status' => ['required', Rule::in(['examen', 'les', 'proefles', 'ziek'])],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'vehicle' => ['nullable', 'string'],
            'package' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'has_guardian' => ['nullable', 'boolean'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_phone' => ['nullable', 'string'],
            'notify_student_email' => ['nullable', 'boolean'],
            'notify_guardian_email' => ['nullable', 'boolean'],
            'notify_student_phone' => ['nullable', 'boolean'],
            'notify_guardian_phone' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'student_birth_date' => ['nullable', 'date'],
        ];

        if ($user->isAdmin()) {
            $rules['instructor_id'] = ['required', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        $student = Student::findOrFail($data['student_id']);

        if (array_key_exists('student_birth_date', $data)) {
            $student->birth_date = $data['student_birth_date'] ?: null;
            if ($student->isDirty('birth_date')) {
                $student->save();
            }
        }

        $event->fill([
            'instructor_id' => $user->isAdmin() ? $data['instructor_id'] : $event->instructor_id,
            'student_id' => $student->id,
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'vehicle' => $data['vehicle'] ?? $event->vehicle,
            'package' => $data['package'] ?? $event->package,
            'email' => array_key_exists('email', $data) ? $data['email'] : $event->email,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $event->phone,
            'has_guardian' => array_key_exists('has_guardian', $data)
                ? (bool) $data['has_guardian']
                : $event->has_guardian,
            'guardian_email' => array_key_exists('guardian_email', $data)
                ? $data['guardian_email']
                : $event->guardian_email,
            'guardian_phone' => array_key_exists('guardian_phone', $data)
                ? $data['guardian_phone']
                : $event->guardian_phone,
            'notify_student_email' => array_key_exists('notify_student_email', $data)
                ? (bool) $data['notify_student_email']
                : $event->notify_student_email,
            'notify_guardian_email' => array_key_exists('notify_guardian_email', $data)
                ? (bool) $data['notify_guardian_email']
                : $event->notify_guardian_email,
            'notify_student_phone' => array_key_exists('notify_student_phone', $data)
                ? (bool) $data['notify_student_phone']
                : $event->notify_student_phone,
            'notify_guardian_phone' => array_key_exists('notify_guardian_phone', $data)
                ? (bool) $data['notify_guardian_phone']
                : $event->notify_guardian_phone,
            'location' => $data['location'] ?? $event->location,
            'description' => $data['description'] ?? $event->description,
        ])->save();

        $event->load(['student', 'instructor']);

        return response()->json($this->formatEvent($event));
    }

    protected function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'instructor_id' => $event->instructor_id,
            'instructor_name' => $event->instructor?->name,
            'student_id' => $event->student_id,
            'student_name' => $event->student?->full_name,
            'student_email' => $event->student?->email,
            'student_phone' => $event->student?->phone,
            'student_has_guardian' => $event->student?->has_guardian,
            'student_guardian_email' => $event->student?->guardian_email,
            'student_guardian_phone' => $event->student?->guardian_phone,
            'student_birth_date' => $event->student?->birth_date?->toDateString(),
            'student_notify_student_email' => $event->student?->notify_student_email,
            'student_notify_guardian_email' => $event->student?->notify_guardian_email,
            'student_notify_student_phone' => $event->student?->notify_student_phone,
            'student_notify_guardian_phone' => $event->student?->notify_guardian_phone,
            'status' => $event->status,
            'start_time' => $event->start_time?->format('Y-m-d\TH:i:s'),
            'end_time' => $event->end_time?->format('Y-m-d\TH:i:s'),
            'vehicle' => $event->vehicle,
            'package' => $event->package,
            'email' => $event->email,
            'phone' => $event->phone,
            'has_guardian' => $event->has_guardian,
            'guardian_email' => $event->guardian_email,
            'guardian_phone' => $event->guardian_phone,
            'notify_student_email' => $event->notify_student_email,
            'notify_guardian_email' => $event->notify_guardian_email,
            'notify_student_phone' => $event->notify_student_phone,
            'notify_guardian_phone' => $event->notify_guardian_phone,
            'location' => $event->location,
            'description' => $event->description,
        ];
    }
}
