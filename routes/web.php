<?php

use App\Http\Controllers\Proctor\AttendanceController;
use App\Http\Controllers\Proctor\DashboardController;
use App\Http\Controllers\Proctor\ExaminationController;
use App\Http\Controllers\Proctor\IncidentController;
use App\Http\Controllers\Proctor\ReportController;
use App\Http\Controllers\Proctor\StudentController;
use App\Http\Controllers\Student\JoinExaminationController;
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
    ->get('student/dashboard', fn () => Inertia::render('student/dashboard'))
    ->name('student.dashboard');

Route::middleware(['auth', 'role:'.User::ROLE_STUDENT])
    ->post('student/examinations/join', JoinExaminationController::class)
    ->name('student.examinations.join');

Route::middleware(['auth', 'role:'.User::ROLE_PROCTOR])
    ->prefix('proctor')
    ->name('proctor.')
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('examinations', [ExaminationController::class, 'index'])->name('examinations.index');
        Route::post('examinations', [ExaminationController::class, 'store'])->name('examinations.store');
        Route::patch('examinations/{examination}/start', [ExaminationController::class, 'start'])->name('examinations.start');
        Route::patch('examinations/{examination}/end', [ExaminationController::class, 'end'])->name('examinations.end');

        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/search', [StudentController::class, 'search'])->name('students.search');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');

        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::put('attendance/{examination}', [AttendanceController::class, 'update'])->name('attendance.update');

        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::patch('incidents/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.status');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
