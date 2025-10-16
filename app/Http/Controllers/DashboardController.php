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

        return view('dashboard', [
            'user' => $user,
            'instructors' => $instructors->map(fn ($instructor) => [
                'id' => $instructor->id,
                'name' => $instructor->name,
            ])->values(),
        ]);
    }
}
