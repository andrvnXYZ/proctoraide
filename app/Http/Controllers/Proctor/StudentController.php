<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proctor\AddStudentRequest;
use App\Http\Requests\Proctor\SearchStudentRequest;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\ProctorAssignments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $students = Enrollment::where('proctor_id', $proctorId)
            ->orderBy('student_name')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'id' => (string) $enrollment->getKey(),
                'student_id' => (string) $enrollment->student_id,
                'student_name' => $enrollment->student_name,
                'student_id_number' => $enrollment->student_id_number,
                'subject' => $enrollment->subject,
                'section' => $enrollment->section,
                'year_level' => $enrollment->year_level,
            ])
            ->values();

        return Inertia::render('proctor/students/index', [
            'students' => $students,
            'sections' => $students->pluck('section')->filter()->unique()->values(),
            'subjects' => $students->pluck('subject')->filter()->unique()->values(),
            'assignments' => ProctorAssignments::pairs($proctorId),
        ]);
    }

    /**
     * Look up an existing student account by its exact Student ID.
     */
    public function search(SearchStudentRequest $request): JsonResponse
    {
        $student = User::where('student_id_number', $request->validated('student_id'))->first();

        if ($student === null) {
            return response()->json(['message' => 'No student found with this Student ID.'], 404);
        }

        if (! $student->isStudent()) {
            return response()->json(['message' => 'This account is not a student account.'], 422);
        }

        return response()->json([
            'student' => [
                'name' => $student->name,
                'student_id_number' => $student->student_id_number,
                'email' => $student->email,
                'year_level' => $student->year_level,
            ],
        ]);
    }

    /**
     * Enroll an existing student account into one of the proctor's subjects.
     */
    public function store(AddStudentRequest $request): RedirectResponse
    {
        $proctorId = (string) $request->user()->getKey();
        $subject = (string) $request->validated('subject');
        $section = (string) $request->validated('section');

        $student = User::where('student_id_number', $request->validated('student_id'))->first();

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_id' => 'No student found with this Student ID.',
            ]);
        }

        if (! $student->isStudent()) {
            throw ValidationException::withMessages([
                'student_id' => 'This account is not a student account.',
            ]);
        }

        if (! ProctorAssignments::owns($proctorId, $subject, $section)) {
            throw ValidationException::withMessages([
                'section' => 'You are not assigned to this subject and section.',
            ]);
        }

        $studentId = (string) $student->getKey();

        $alreadyEnrolled = Enrollment::where('student_id', $studentId)
            ->where('subject', $subject)
            ->where('section', $section)
            ->exists();

        if ($alreadyEnrolled) {
            throw ValidationException::withMessages([
                'student_id' => 'This student is already enrolled in this subject and section.',
            ]);
        }

        // Only server-side values from the existing account are stored.
        Enrollment::create([
            'proctor_id' => $proctorId,
            'student_id' => $studentId,
            'student_name' => $student->name,
            'student_id_number' => $student->student_id_number,
            'subject' => $subject,
            'section' => $section,
            'year_level' => $student->year_level,
        ]);

        return redirect()
            ->route('proctor.students.index')
            ->with('success', 'Student successfully added!');
    }
}
