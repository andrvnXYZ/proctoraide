<?php

namespace Tests\Feature\Proctor;

use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationParticipant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class StudentEnrollmentTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_proctor_can_search_for_an_existing_student_by_student_id(): void
    {
        $proctor = User::factory()->proctor()->create();
        User::factory()->create([
            'name' => 'Ana Cruz',
            'student_id_number' => '2024-01001',
            'email' => 'ana.cruz@student.proctoraide.test',
            'year_level' => '2nd Year',
        ]);

        $this->actingAs($proctor)
            ->getJson(route('proctor.students.search', ['student_id' => '2024-01001']))
            ->assertOk()
            ->assertJsonPath('student.name', 'Ana Cruz')
            ->assertJsonPath('student.student_id_number', '2024-01001')
            ->assertJsonPath('student.email', 'ana.cruz@student.proctoraide.test')
            ->assertJsonPath('student.year_level', '2nd Year')
            ->assertJsonMissingPath('student.password')
            ->assertJsonMissingPath('student.remember_token');
    }

    public function test_unknown_student_id_is_rejected(): void
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->getJson(route('proctor.students.search', ['student_id' => '2024-99999']))
            ->assertNotFound()
            ->assertJsonPath('message', 'No student found with this Student ID.');

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-99999',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertSessionHasErrors(['student_id' => 'No student found with this Student ID.']);
    }

    public function test_proctor_accounts_cannot_be_enrolled_as_students(): void
    {
        $proctor = User::factory()->proctor()->create();
        User::factory()->proctor()->create([
            'name' => 'Other Proctor',
            'student_id_number' => 'PROC-0001',
        ]);

        $this->actingAs($proctor)
            ->getJson(route('proctor.students.search', ['student_id' => 'PROC-0001']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'This account is not a student account.');

        $this->assignPair($proctor, 'Networking 2', 'BSIT-3A');

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => 'PROC-0001',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertSessionHasErrors(['student_id' => 'This account is not a student account.']);

        $this->assertSame(1, Enrollment::count(), 'Only the proctor assignment record should exist.');
    }

    public function test_proctor_can_enroll_an_existing_student(): void
    {
        $proctor = User::factory()->proctor()->create();
        $student = User::factory()->create([
            'name' => 'Maria Santos',
            'student_id_number' => '2024-01002',
            'year_level' => '3rd Year',
        ]);

        $this->assignPair($proctor, 'Networking 2', 'BSIT-3A');

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01002',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertRedirect(route('proctor.students.index'))
            ->assertSessionHas('success', 'Student successfully added!')
            ->assertSessionHasNoErrors();

        $enrollment = Enrollment::where('student_id', (string) $student->getKey())->first();

        $this->assertNotNull($enrollment);
        $this->assertSame((string) $proctor->getKey(), (string) $enrollment->proctor_id);
        $this->assertSame((string) $student->getKey(), (string) $enrollment->student_id);
        $this->assertSame('Maria Santos', $enrollment->student_name);
        $this->assertSame('2024-01002', $enrollment->student_id_number);
        $this->assertSame('Networking 2', $enrollment->subject);
        $this->assertSame('BSIT-3A', $enrollment->section);
        $this->assertSame('3rd Year', $enrollment->year_level);

        // The roster the proctor returns to already contains the new student.
        $this->actingAs($proctor)
            ->get(route('proctor.students.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('proctor/students/index')
                ->where('students', fn ($students) => collect($students)->contains(
                    fn ($item) => $item['student_id_number'] === '2024-01002' && $item['section'] === 'BSIT-3A',
                ))
                ->where('assignments', fn ($assignments) => collect($assignments)->contains(
                    fn ($pair) => $pair['subject'] === 'Networking 2' && $pair['section'] === 'BSIT-3A',
                )));
    }

    public function test_student_information_is_taken_from_the_user_record_not_the_request(): void
    {
        $proctor = User::factory()->proctor()->create();
        $student = User::factory()->create([
            'name' => 'Real Name',
            'student_id_number' => '2024-01003',
            'year_level' => '1st Year',
        ]);

        $this->assignPair($proctor, 'Networking 2', 'BSIT-3A');

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01003',
                'student_name' => 'Forged Name',
                'year_level' => '5th Year',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertSessionHasNoErrors();

        $enrollment = Enrollment::where('student_id', (string) $student->getKey())->firstOrFail();

        $this->assertSame('Real Name', $enrollment->student_name);
        $this->assertSame('1st Year', $enrollment->year_level);

        $student->refresh();

        $this->assertSame(User::ROLE_STUDENT, $student->role);
        $this->assertSame('2024-01003', $student->student_id_number);
    }

    public function test_duplicate_enrollments_are_prevented(): void
    {
        $proctor = User::factory()->proctor()->create();
        User::factory()->create(['student_id_number' => '2024-01004']);

        $this->assignPair($proctor, 'Networking 2', 'BSIT-3A');

        $payload = [
            'student_id' => '2024-01004',
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ];

        $this->actingAs($proctor)->post(route('proctor.students.store'), $payload)->assertSessionHasNoErrors();

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), $payload)
            ->assertSessionHasErrors(['student_id' => 'This student is already enrolled in this subject and section.']);

        $this->assertSame(1, Enrollment::where('student_id_number', '2024-01004')->count());
    }

    public function test_proctor_cannot_enroll_into_an_unassigned_subject_or_section(): void
    {
        $proctor = User::factory()->proctor()->create();
        $otherProctor = User::factory()->proctor()->create();
        User::factory()->create(['student_id_number' => '2024-01005']);

        // The pair belongs to a different proctor, not the authenticated one.
        $this->assignPair($otherProctor, 'Data Structures', 'BSCS-4A');
        $this->assignPair($proctor, 'Networking 2', 'BSIT-3A');

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01005',
                'subject' => 'Data Structures',
                'section' => 'BSCS-4A',
            ])
            ->assertSessionHasErrors(['section' => 'You are not assigned to this subject and section.']);

        $this->assertSame(0, Enrollment::where('student_id_number', '2024-01005')->count());

        // A section the proctor owns, paired with a subject they do not, is also rejected.
        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01005',
                'subject' => 'Data Structures',
                'section' => 'BSIT-3A',
            ])
            ->assertSessionHasErrors(['section' => 'You are not assigned to this subject and section.']);

        $this->assertSame(0, Enrollment::where('student_id_number', '2024-01005')->count());
    }

    public function test_newly_enrolled_student_can_join_a_matching_ongoing_examination(): void
    {
        $proctor = User::factory()->proctor()->create();
        $student = User::factory()->create(['student_id_number' => '2024-01006']);

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
        ]);

        $this->actingAs($proctor)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01006',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($student)
            ->post(route('student.examinations.join'), ['access_code' => $examination->access_code])
            ->assertSessionHas('success', 'Successfully joined the examination!')
            ->assertSessionHasNoErrors();

        $participant = ExaminationParticipant::where('student_id', (string) $student->getKey())->first();

        $this->assertNotNull($participant);
        $this->assertSame((string) $examination->getKey(), (string) $participant->examination_id);
    }

    public function test_students_cannot_search_for_or_add_students(): void
    {
        $student = User::factory()->create(['student_id_number' => '2024-01007']);
        $classmate = User::factory()->create(['student_id_number' => '2024-01008']);

        $this->actingAs($student)
            ->getJson(route('proctor.students.search', ['student_id' => '2024-01008']))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('proctor.students.store'), [
                'student_id' => '2024-01008',
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
            ])
            ->assertForbidden();

        $this->assertSame(0, Enrollment::count());
        $this->assertNotNull($classmate);
    }

    /**
     * Give the proctor an existing subject/section assignment, mirroring how the
     * project already represents assignments (through enrollments).
     */
    private function assignPair(User $proctor, string $subject, string $section): Enrollment
    {
        $placeholder = User::factory()->create();

        return Enrollment::factory()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'student_id' => (string) $placeholder->getKey(),
            'subject' => $subject,
            'section' => $section,
        ]);
    }
}
