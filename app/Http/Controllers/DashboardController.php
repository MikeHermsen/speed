<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = (clone $weekStart)->endOfWeek(Carbon::SUNDAY);

        $eventsQuery = Event::with(['student', 'instructor'])
            ->whereBetween('start_time', [$weekStart, $weekEnd]);

        if ($user->isInstructor()) {
            $eventsQuery->where('instructor_id', $user->id);
        }

        $events = $eventsQuery
            ->orderBy('start_time')
            ->get()
            ->map(function (Event $event) {
                return [
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
                ];
            })
            ->values();

        $instructors = $user->isAdmin()
            ? User::instructors()->orderBy('name')->get()
            : collect([$user]);

        return view('dashboard', [
            'user' => $user,
            'weekStart' => $weekStart->toIso8601String(),
            'events' => $events,
            'instructors' => $instructors->map(fn ($instructor) => [
                'id' => $instructor->id,
                'name' => $instructor->name,
            ])->values(),
        ]);
    }
}
