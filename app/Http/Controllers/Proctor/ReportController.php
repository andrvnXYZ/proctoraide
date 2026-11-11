<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Support\AttendanceSummary;
use App\Support\ExaminationPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $examinations = Examination::where('proctor_id', $proctorId)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->get();

        $reports = $examinations->map(function (Examination $examination) use ($proctorId) {
            $attendance = AttendanceSummary::forExamination($examination, $proctorId);
            $total = $attendance['total'];

            return [
                ...ExaminationPresenter::toArray($examination, $total),
                'counts' => $attendance['counts'],
                'recorded' => $attendance['recorded'],
                'attendance_rate' => $total > 0
                    ? round((($attendance['counts']['present'] + $attendance['counts']['late']) / $total) * 100)
                    : 0,
            ];
        })->values();

        $requestedId = $request->query('examination');
        $selected = $requestedId
            ? $examinations->first(fn (Examination $examination) => (string) $examination->getKey() === $requestedId)
            : null;

        return Inertia::render('proctor/reports/index', [
            'reports' => $reports,
            'selectedExamination' => $selected ? ExaminationPresenter::toArray($selected) : null,
            'selectedAttendance' => $selected ? AttendanceSummary::forExamination($selected, $proctorId) : null,
        ]);
    }
}
