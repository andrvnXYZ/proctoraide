<?php

namespace Database\Factories;

use App\Models\Examination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Examination>
 */
class ExaminationFactory extends Factory
{
    protected $model = Examination::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proctor_id' => (string) fake()->numberBetween(1, 999999),
            'title' => fake()->randomElement(['Midterm Exam', 'Final Exam', 'Quiz', 'Long Examination']),
            'subject' => fake()->randomElement(['Networking 2', 'Mobile Programming', 'Data Structures', 'Database Management']),
            'section' => fake()->randomElement(['BSIT-3A', 'BSIT-3B', 'BSCS-4A']),
            'scheduled_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'access_code' => Examination::generateAccessCode(),
            'status' => Examination::STATUS_SCHEDULED,
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    public function ongoing(): static
    {
        return $this->state(fn () => [
            'status' => Examination::STATUS_ONGOING,
            'scheduled_date' => now()->format('Y-m-d'),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => Examination::STATUS_COMPLETED,
            'scheduled_date' => now()->subDay()->format('Y-m-d'),
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay()->addHour(),
        ]);
    }
}
