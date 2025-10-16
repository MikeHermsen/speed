<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $eventsQuery = Event::with(['student', 'instructor'])
            ->whereBetween('start_time', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);

        if ($user->isInstructor()) {
            $eventsQuery->where('instructor_id', $user->id);
        } elseif ($request->filled('instructor_id')) {
            $eventsQuery->where('instructor_id', $request->integer('instructor_id'));
        }

        $events = $eventsQuery->orderBy('start_time')->get()->map(fn ($event) => [
            'id' => $event->id,
            'instructor_id' => $event->instructor_id,
            'instructor_name' => $event->instructor?->name,
            'student_id' => $event->student_id,
            'student_name' => $event->student?->full_name,
            'status' => $event->status,
            'start_time' => $event->start_time?->toIso8601String(),
            'end_time' => $event->end_time?->toIso8601String(),
            'vehicle' => $event->vehicle,
            'package' => $event->package,
            'email' => $event->email,
            'phone' => $event->phone,
            'location' => $event->location,
            'description' => $event->description,
        ]);

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

        return response()->json([
            'id' => $event->id,
            'instructor_id' => $event->instructor_id,
            'instructor_name' => $event->instructor?->name,
            'student_id' => $event->student_id,
            'student_name' => $event->student?->full_name,
            'status' => $event->status,
            'start_time' => $event->start_time?->toIso8601String(),
            'end_time' => $event->end_time?->toIso8601String(),
            'vehicle' => $event->vehicle,
            'package' => $event->package,
            'email' => $event->email,
            'phone' => $event->phone,
            'location' => $event->location,
            'description' => $event->description,
        ], 201);
    }
}
