<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\Incident;
use App\Support\ExaminationPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $activeExaminations = Examination::where('proctor_id', $proctorId)
            ->where('status', Examination::STATUS_ONGOING)
            ->count();

        $reportedIncidents = Incident::where('proctor_id', $proctorId)->count();

        $pendingIncidents = Incident::where('proctor_id', $proctorId)
            ->where('status', Incident::STATUS_PENDING)
            ->count();

        $activeStudents = Enrollment::where('proctor_id', $proctorId)
            ->pluck('student_id')
            ->unique()
            ->count();

        $upcomingExaminations = Examination::where('proctor_id', $proctorId)
            ->whereIn('status', [Examination::STATUS_ONGOING, Examination::STATUS_SCHEDULED])
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn (Examination $examination) => ExaminationPresenter::toArray($examination))
            ->values();

        $totalExaminations = Examination::where('proctor_id', $proctorId)->count();

        return Inertia::render('proctor/dashboard', [
            'stats' => [
                'active_examinations' => $activeExaminations,
                'reported_incidents' => $reportedIncidents,
                'pending_incidents' => $pendingIncidents,
                'active_students' => $activeStudents,
            ],
            'upcomingExaminations' => $upcomingExaminations,
            'totalExaminations' => $totalExaminations,
        ]);
    }
}
