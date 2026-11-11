<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for the proctor dashboard.
 *
 * Run it manually with: php artisan db:seed --class=ProctorDemoSeeder
 * It is idempotent, so running it twice does not duplicate records.
 */
class ProctorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $proctor = User::firstOrCreate(
            ['email' => 'proctor@proctoraide.test'],
            [
                'name' => 'John Baguas',
                'first_name' => 'John',
                'last_name' => 'Baguas',
                'email_verified_at' => now(),
                'password' => Hash::make('Proctor123'),
                'role' => User::ROLE_PROCTOR,
                'college' => 'College of Information Technology',
                'department' => 'USTP · CDO',
                'terms_accepted_at' => now(),
            ],
        );

        $proctorId = (string) $proctor->getKey();

        $roster = [
            ['BSIT-3A', 'Networking 2', ['Maria Santos', 'Jose Rizal Jr.', 'Andrea Cruz', 'Miguel Reyes', 'Bea Villanueva']],
            ['BSIT-3B', 'Mobile Programming', ['Carlo Mendoza', 'Liza Fernandez', 'Noel Aquino', 'Trisha Lim', 'Paolo Garcia']],
        ];

        foreach ($roster as [$section, $subject, $names]) {
            foreach ($names as $index => $name) {
                $student = User::firstOrCreate(
                    ['email' => strtolower(str_replace([' ', '.'], ['.', ''], $name)).'@student.proctoraide.test'],
                    [
                        'name' => $name,
                        'first_name' => explode(' ', $name)[0],
                        'last_name' => explode(' ', $name)[1] ?? '',
                        'student_id_number' => '2024-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                        'email_verified_at' => now(),
                        'password' => Hash::make('Student123'),
                        'role' => User::ROLE_STUDENT,
                        'college' => 'College of Information Technology',
                        'department' => 'USTP · CDO',
                        'year_level' => '3rd Year',
                        'terms_accepted_at' => now(),
                    ],
                );

                Enrollment::updateOrCreate(
                    ['proctor_id' => $proctorId, 'student_id' => (string) $student->getKey()],
                    [
                        'student_name' => $student->name,
                        'student_id_number' => $student->student_id_number,
                        'subject' => $subject,
                        'section' => $section,
                        'year_level' => $student->year_level,
                    ],
                );
            }
        }

        $ongoing = Examination::firstOrCreate(
            ['proctor_id' => $proctorId, 'title' => 'Midterm Exam'],
            [
                'subject' => 'Networking 2',
                'section' => 'BSIT-3A',
                'scheduled_date' => now()->format('Y-m-d'),
                'start_time' => '13:00',
                'end_time' => '14:00',
                'access_code' => Examination::generateAccessCode(),
                'status' => Examination::STATUS_ONGOING,
                'started_at' => now(),
            ],
        );

        Examination::firstOrCreate(
            ['proctor_id' => $proctorId, 'title' => 'Quiz 3'],
            [
                'subject' => 'Mobile Programming',
                'section' => 'BSIT-3B',
                'scheduled_date' => now()->format('Y-m-d'),
                'start_time' => '19:30',
                'end_time' => '20:30',
                'access_code' => Examination::generateAccessCode(),
                'status' => Examination::STATUS_SCHEDULED,
            ],
        );

        $completed = Examination::firstOrCreate(
            ['proctor_id' => $proctorId, 'title' => 'Final Exam'],
            [
                'subject' => 'Mobile Programming',
                'section' => 'BSIT-3B',
                'scheduled_date' => now()->subDay()->format('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '09:30',
                'access_code' => Examination::generateAccessCode(),
                'status' => Examination::STATUS_COMPLETED,
                'started_at' => now()->subDay(),
                'ended_at' => now()->subDay()->addMinutes(90),
            ],
        );

        $completedRoster = Enrollment::where('proctor_id', $proctorId)
            ->where('section', 'BSIT-3B')
            ->orderBy('student_name')
            ->get()
            ->values();

        $savedStatuses = ['present', 'present', 'late', 'absent'];

        foreach ($completedRoster as $index => $enrollment) {
            if (! isset($savedStatuses[$index])) {
                // The remaining students stay "not yet checked".
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'examination_id' => (string) $completed->getKey(),
                    'enrollment_id' => (string) $enrollment->getKey(),
                ],
                [
                    'proctor_id' => $proctorId,
                    'student_id' => (string) $enrollment->student_id,
                    'student_name' => $enrollment->student_name,
                    'student_id_number' => $enrollment->student_id_number,
                    'status' => $savedStatuses[$index],
                    'recorded_by' => $proctorId,
                    'recorded_at' => now()->subDay()->addMinutes(30),
                ],
            );
        }

        Incident::firstOrCreate(
            ['proctor_id' => $proctorId, 'description' => 'Student asked to leave the room during the examination without notifying the proctor.'],
            [
                'examination_id' => (string) $ongoing->getKey(),
                'examination_title' => $ongoing->title,
                'student_name' => $completedRoster->first()?->student_name,
                'student_id_number' => $completedRoster->first()?->student_id_number,
                'status' => Incident::STATUS_PENDING,
                'reported_by' => $proctorId,
                'reported_at' => now()->subMinutes(20),
            ],
        );
    }
}
