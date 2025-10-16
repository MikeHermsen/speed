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
            'location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];

        if ($user->isAdmin()) {
            $rules['instructor_id'] = ['required', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        $student = Student::findOrFail($data['student_id']);

        $instructorId = $user->isAdmin() ? $data['instructor_id'] : $user->id;

        $event = Event::create([
            'instructor_id' => $instructorId,
            'student_id' => $student->id,
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'vehicle' => $data['vehicle'] ?? $student->vehicle,
            'package' => $data['package'] ?? $student->package,
            'email' => $data['email'] ?? $student->email,
            'phone' => $data['phone'] ?? $student->phone,
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
            'location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];

        if ($user->isAdmin()) {
            $rules['instructor_id'] = ['required', 'exists:users,id'];
        }

        $data = $request->validate($rules);

        $student = Student::findOrFail($data['student_id']);

        $event->fill([
            'instructor_id' => $user->isAdmin() ? $data['instructor_id'] : $event->instructor_id,
            'student_id' => $student->id,
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'vehicle' => $data['vehicle'] ?? $event->vehicle,
            'package' => $data['package'] ?? $event->package,
            'email' => $data['email'] ?? $event->email,
            'phone' => $data['phone'] ?? $event->phone,
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
            'status' => $event->status,
            'start_time' => $event->start_time?->toIso8601String(),
            'end_time' => $event->end_time?->toIso8601String(),
            'vehicle' => $event->vehicle,
            'package' => $event->package,
            'email' => $event->email,
            'phone' => $event->phone,
            'location' => $event->location,
            'description' => $event->description,
        ];
    }
}
