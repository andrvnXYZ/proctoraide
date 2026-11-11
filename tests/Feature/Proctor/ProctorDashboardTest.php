<?php

namespace Tests\Feature\Proctor;

use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\Incident;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class ProctorDashboardTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_dashboard_statistics_come_from_database_records(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $students = User::factory()->count(3)->create();

        foreach ($students as $student) {
            Enrollment::factory()->create([
                'proctor_id' => $proctorId,
                'student_id' => (string) $student->getKey(),
                'section' => 'BSIT-3A',
            ]);
        }

        Examination::factory()->count(2)->ongoing()->create(['proctor_id' => $proctorId]);
        Examination::factory()->create(['proctor_id' => $proctorId, 'status' => Examination::STATUS_SCHEDULED]);
        Examination::factory()->completed()->create(['proctor_id' => $proctorId]);

        Incident::create([
            'proctor_id' => $proctorId,
            'examination_id' => 'exam-1',
            'description' => 'Student left the room without permission.',
            'status' => Incident::STATUS_PENDING,
            'reported_by' => $proctorId,
            'reported_at' => now(),
        ]);

        $this->actingAs($proctor)
            ->get(route('proctor.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('proctor/dashboard')
                ->where('stats.active_examinations', 2)
                ->where('stats.reported_incidents', 1)
                ->where('stats.pending_incidents', 1)
                ->where('stats.active_students', 3)
                ->has('upcomingExaminations', 3));
    }

    public function test_dashboard_lists_only_upcoming_and_ongoing_examinations_of_the_proctor(): void
    {
        $proctor = User::factory()->proctor()->create();

        Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'title' => 'Midterm Exam',
        ]);

        Examination::factory()->completed()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'title' => 'Finished Exam',
        ]);

        $this->actingAs($proctor)
            ->get(route('proctor.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcomingExaminations', 1)
                ->where('upcomingExaminations.0.title', 'Midterm Exam'));
    }

    public function test_dashboard_shows_an_empty_state_without_records(): void
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->get(route('proctor.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.active_examinations', 0)
                ->where('stats.reported_incidents', 0)
                ->where('stats.active_students', 0)
                ->has('upcomingExaminations', 0));
    }

    public function test_students_cannot_access_any_proctor_page(): void
    {
        $student = User::factory()->create();

        $routes = [
            route('proctor.dashboard'),
            route('proctor.examinations.index'),
            route('proctor.students.index'),
            route('proctor.attendance.index'),
            route('proctor.incidents.index'),
            route('proctor.reports.index'),
        ];

        foreach ($routes as $route) {
            $this->actingAs($student)->get($route)->assertForbidden();
        }
    }
}
