<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $dashboard = $request->user()->dashboardRouteName();

        abort_if($dashboard === null, 403);

        return redirect()->route($dashboard);
    })->name('dashboard');
});

Route::middleware(['auth', 'role:'.User::ROLE_STUDENT])
    ->get('student/dashboard', fn () => Inertia::render('dashboard'))
    ->name('student.dashboard');

Route::middleware(['auth', 'role:'.User::ROLE_PROCTOR])
    ->get('proctor/dashboard', fn () => Inertia::render('dashboard'))
    ->name('proctor.dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
