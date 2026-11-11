<?php

namespace Tests\Feature\Student;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationParticipant;
use App\Models\User;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class JoinExaminationTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_student_can_join_an_ongoing_examination_with_a_valid_code(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => strtolower($examination->access_code)])
            ->assertRedirect()
            ->assertSessionHas('success', 'Successfully joined the examination!')
            ->assertSessionHas('joined_examination.title', $examination->title)
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ExaminationParticipant::count());

        $participant = ExaminationParticipant::first();

        $this->assertSame((string) $student->getKey(), $participant->student_id);
        $this->assertSame((string) $examination->getKey(), $participant->examination_id);
        $this->assertSame('BSIT-3A', $participant->section);
        $this->assertNotNull($participant->joined_at);
    }

    public function test_joining_does_not_mark_attendance_or_change_the_session(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, AttendanceRecord::count());

        $examination->refresh();

        $this->assertSame(Examination::STATUS_ONGOING, $examination->status);
        $this->assertNull($examination->ended_at);
    }

    public function test_invalid_access_code_is_rejected(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create();

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => '000000'])
            ->assertRedirect()
            ->assertSessionHasErrors(['access_code' => 'Invalid examination access code.']);

        $this->assertSame(0, ExaminationParticipant::count());
    }

    public function test_access_code_must_be_six_alphanumeric_characters(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => 'AB1'])
            ->assertSessionHasErrors(['access_code' => 'The access code must be exactly 6 characters.']);
    }

    public function test_student_outside_the_enrolled_section_cannot_join(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        Enrollment::factory()->create([
            'student_id' => (string) $student->getKey(),
            'subject' => 'Networking 2',
            'section' => 'BSIT-3B',
        ]);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasErrors(['access_code' => 'You are not enrolled in this examination.']);

        $this->assertSame(0, ExaminationParticipant::count());
    }

    public function test_student_cannot_join_a_scheduled_examination(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->create([
            'status' => Examination::STATUS_SCHEDULED,
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasErrors(['access_code' => 'This examination has not started yet.']);

        $this->assertSame(0, ExaminationParticipant::count());
    }

    public function test_student_cannot_join_a_completed_examination(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->completed()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasErrors(['access_code' => 'This examination has already ended.']);

        $this->assertSame(0, ExaminationParticipant::count());
    }

    public function test_duplicate_joins_are_prevented(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasNoErrors();

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasErrors(['access_code' => 'You have already joined this examination.']);

        $this->assertSame(1, ExaminationParticipant::count());
    }

    public function test_five_incorrect_codes_trigger_a_one_minute_lockout(): void
    {
        $student = User::factory()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->enroll($student, $examination);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($student)
                ->post(route('student.examinations.join'), ['access_code' => '000000'])
                ->assertSessionHasErrors('access_code');
        }

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasErrors('access_code');

        $this->assertSame(0, ExaminationParticipant::count());

        $this->travel(61)->seconds();

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ExaminationParticipant::count());
    }

    public function test_proctors_cannot_use_the_student_join_endpoint(): void
    {
        $proctor = User::factory()->proctor()->create();
        $examination = Examination::factory()->ongoing()->create([
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->actingAs($proctor)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertForbidden();

        $this->assertSame(0, ExaminationParticipant::count());
    }

    private function enroll(User $student, Examination $examination): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => (string) $student->getKey(),
            'subject' => $examination->subject,
            'section' => $examination->section,
        ]);
    }
}
