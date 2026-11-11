<?php

namespace Tests\Feature\Proctor;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\Incident;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class IncidentAndReportTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_proctor_can_submit_an_incident_for_an_enrolled_student(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        $enrollment = Enrollment::factory()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
            'student_name' => 'Maria Santos',
        ]);

        $this->actingAs($proctor)
            ->post(route('proctor.incidents.store'), [
                'examination_id' => (string) $examination->getKey(),
                'student_id' => (string) $enrollment->getKey(),
                'description' => 'Student was talking loudly during the examination.',
            ])
            ->assertRedirect(route('proctor.incidents.index'))
            ->assertSessionHas('success');

        $incident = Incident::first();

        $this->assertNotNull($incident);
        $this->assertSame('Maria Santos', $incident->student_name);
        $this->assertSame(Incident::STATUS_PENDING, $incident->status);
        $this->assertSame($proctorId, (string) $incident->reported_by);
    }

    public function test_incident_can_be_submitted_without_a_specific_student(): void
    {
        $proctor = User::factory()->proctor()->create();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $proctor->getKey(),
        ]);

        $this->actingAs($proctor)
            ->post(route('proctor.incidents.store'), [
                'examination_id' => (string) $examination->getKey(),
                'student_id' => '',
                'description' => 'An unattended bag was found near the door.',
            ])
            ->assertRedirect(route('proctor.incidents.index'));

        $this->assertNull(Incident::first()->student_id);
    }

    public function test_incident_report_requires_a_description_and_own_examination(): void
    {
        $proctor = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        $foreign = Examination::factory()->create(['proctor_id' => (string) $other->getKey()]);

        $this->actingAs($proctor)
            ->post(route('proctor.incidents.store'), [
                'examination_id' => (string) $foreign->getKey(),
                'description' => 'The student was using a phone during the examination.',
            ])
            ->assertSessionHasErrors('examination_id');

        $this->actingAs($proctor)
            ->post(route('proctor.incidents.store'), [
                'examination_id' => (string) $foreign->getKey(),
                'description' => 'Short',
            ])
            ->assertSessionHasErrors('description');

        $this->assertSame(0, Incident::count());
    }

    public function test_student_from_another_section_cannot_be_attached_to_an_incident(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        $enrollment = Enrollment::factory()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3B',
        ]);

        $this->actingAs($proctor)
            ->post(route('proctor.incidents.store'), [
                'examination_id' => (string) $examination->getKey(),
                'student_id' => (string) $enrollment->getKey(),
                'description' => 'Student was using a phone during the examination.',
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(0, Incident::count());
    }

    public function test_incident_status_can_be_reviewed_and_dismissed_by_the_owner(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $incident = Incident::create([
            'proctor_id' => $proctorId,
            'examination_id' => 'exam-1',
            'description' => 'Student left the room without permission.',
            'status' => Incident::STATUS_PENDING,
            'reported_by' => $proctorId,
            'reported_at' => now(),
        ]);

        $this->actingAs($proctor)
            ->patch(route('proctor.incidents.status', $incident), ['status' => Incident::STATUS_REVIEWED])
            ->assertRedirect();

        $this->assertSame(Incident::STATUS_REVIEWED, $incident->refresh()->status);
        $this->assertNotNull($incident->reviewed_at);

        $this->actingAs($proctor)
            ->patch(route('proctor.incidents.status', $incident), ['status' => Incident::STATUS_DISMISSED])
            ->assertRedirect();

        $this->assertSame(Incident::STATUS_DISMISSED, $incident->refresh()->status);
    }

    public function test_another_proctor_cannot_update_an_incident(): void
    {
        $owner = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        $incident = Incident::create([
            'proctor_id' => (string) $owner->getKey(),
            'examination_id' => 'exam-1',
            'description' => 'Student left the room without permission.',
            'status' => Incident::STATUS_PENDING,
            'reported_by' => (string) $owner->getKey(),
            'reported_at' => now(),
        ]);

        $this->actingAs($other)
            ->patch(route('proctor.incidents.status', $incident), ['status' => Incident::STATUS_DISMISSED])
            ->assertForbidden();

        $this->assertSame(Incident::STATUS_PENDING, $incident->refresh()->status);
    }

    public function test_report_summarises_saved_attendance_only(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->completed()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
            'title' => 'Final Exam',
        ]);

        $present = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);
        $absent = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);
        Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);

        foreach ([[$present, 'present'], [$absent, 'absent']] as [$enrollment, $status]) {
            AttendanceRecord::create([
                'proctor_id' => $proctorId,
                'examination_id' => (string) $examination->getKey(),
                'enrollment_id' => (string) $enrollment->getKey(),
                'student_id' => (string) $enrollment->student_id,
                'student_name' => $enrollment->student_name,
                'status' => $status,
                'recorded_by' => $proctorId,
                'recorded_at' => now(),
            ]);
        }

        $this->actingAs($proctor)
            ->get(route('proctor.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('proctor/reports/index')
                ->has('reports', 1)
                ->where('reports.0.counts.present', 1)
                ->where('reports.0.counts.absent', 1)
                ->where('reports.0.counts.not_checked', 1)
                ->where('reports.0.recorded', 2)
                ->where('reports.0.attendance_rate', 33));
    }

    public function test_report_detail_lists_each_student_status(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->completed()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A', 'student_name' => 'Maria Santos']);

        $this->actingAs($proctor)
            ->get(route('proctor.reports.index', ['examination' => (string) $examination->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedExamination.title', $examination->title)
                ->has('selectedAttendance.rows', 1)
                ->where('selectedAttendance.rows.0.status', 'not_checked'));
    }
}
