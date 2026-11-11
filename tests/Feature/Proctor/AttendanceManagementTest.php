<?php

namespace Tests\Feature\Proctor;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_attendance_page_lists_students_from_the_examination_section(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A', 'student_name' => 'Maria Santos']);
        Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3B', 'student_name' => 'Carlo Mendoza']);

        $this->actingAs($proctor)
            ->get(route('proctor.attendance.index', ['examination' => (string) $examination->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('proctor/attendance/index')
                ->has('attendance.rows', 1)
                ->where('attendance.rows.0.student_name', 'Maria Santos')
                ->where('attendance.rows.0.status', 'not_checked')
                ->where('attendance.counts.not_checked', 1));
    }

    public function test_proctor_can_save_attendance_and_unchecked_students_stay_unchecked(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        $present = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);
        $late = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);
        $unchecked = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);

        $this->actingAs($proctor)
            ->put(route('proctor.attendance.update', $examination), [
                'statuses' => [
                    (string) $present->getKey() => 'present',
                    (string) $late->getKey() => 'late',
                    (string) $unchecked->getKey() => 'not_checked',
                ],
            ])
            ->assertRedirect(route('proctor.attendance.index', ['examination' => (string) $examination->getKey()]))
            ->assertSessionHas('success');

        $this->assertSame(2, AttendanceRecord::count());
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => (string) $present->getKey(),
            'status' => 'present',
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'enrollment_id' => (string) $unchecked->getKey(),
        ]);
    }

    public function test_clearing_a_status_removes_the_attendance_record(): void
    {
        $proctor = User::factory()->proctor()->create();
        $proctorId = (string) $proctor->getKey();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => $proctorId,
            'section' => 'BSIT-3A',
        ]);

        $enrollment = Enrollment::factory()->create(['proctor_id' => $proctorId, 'section' => 'BSIT-3A']);

        AttendanceRecord::create([
            'proctor_id' => $proctorId,
            'examination_id' => (string) $examination->getKey(),
            'enrollment_id' => (string) $enrollment->getKey(),
            'student_id' => (string) $enrollment->student_id,
            'student_name' => $enrollment->student_name,
            'status' => 'absent',
            'recorded_by' => $proctorId,
            'recorded_at' => now(),
        ]);

        $this->actingAs($proctor)
            ->put(route('proctor.attendance.update', $examination), [
                'statuses' => [(string) $enrollment->getKey() => 'not_checked'],
            ])
            ->assertRedirect();

        $this->assertSame(0, AttendanceRecord::count());
    }

    public function test_attendance_cannot_be_saved_for_an_examination_owned_by_another_proctor(): void
    {
        $owner = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $owner->getKey(),
            'section' => 'BSIT-3A',
        ]);

        $enrollment = Enrollment::factory()->create([
            'proctor_id' => (string) $other->getKey(),
            'section' => 'BSIT-3A',
        ]);

        $this->actingAs($other)
            ->put(route('proctor.attendance.update', $examination), [
                'statuses' => [(string) $enrollment->getKey() => 'present'],
            ])
            ->assertForbidden();

        $this->assertSame(0, AttendanceRecord::count());
    }

    public function test_attendance_statuses_are_validated(): void
    {
        $proctor = User::factory()->proctor()->create();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'section' => 'BSIT-3A',
        ]);

        $enrollment = Enrollment::factory()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'section' => 'BSIT-3A',
        ]);

        $this->actingAs($proctor)
            ->put(route('proctor.attendance.update', $examination), [
                'statuses' => [(string) $enrollment->getKey() => 'cheating'],
            ])
            ->assertSessionHasErrors(['statuses.'.(string) $enrollment->getKey()]);

        $this->assertSame(0, AttendanceRecord::count());
    }

    public function test_a_proctor_only_sees_students_from_their_own_sections(): void
    {
        $proctor = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        Enrollment::factory()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'student_name' => 'Maria Santos',
        ]);

        Enrollment::factory()->create([
            'proctor_id' => (string) $other->getKey(),
            'student_name' => 'Carlo Mendoza',
        ]);

        $this->actingAs($proctor)
            ->get(route('proctor.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('proctor/students/index')
                ->has('students', 1)
                ->where('students.0.student_name', 'Maria Santos'));
    }

    public function test_attendance_page_accepts_only_one_of_its_own_examinations(): void
    {
        $owner = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        $foreign = Examination::factory()->create([
            'proctor_id' => (string) $other->getKey(),
            'title' => 'Foreign Exam',
        ]);

        Examination::factory()->create([
            'proctor_id' => (string) $owner->getKey(),
            'title' => 'Own Exam',
        ]);

        $this->actingAs($owner)
            ->get(route('proctor.attendance.index', ['examination' => (string) $foreign->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('selectedExamination.title', 'Own Exam'));
    }
}
