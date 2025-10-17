<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $instructors = $user->isAdmin()
            ? User::instructors()->orderBy('name')->get()
            : collect([$user]);

        $instructorOptions = $instructors->map(fn ($instructor) => [
            'id' => $instructor->id,
            'name' => $instructor->name,
        ])->values();

        return view('dashboard', [
            'user' => $user,
            'instructors' => $instructorOptions,
            'planningConfig' => [
                'csrfToken' => csrf_token(),
                'userRole' => $user->role,
                'instructors' => $instructorOptions,
            ],
        ]);
    }
}
