<?php

namespace Database\Factories;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'proctor_id' => (string) fake()->numberBetween(1, 999999),
            'student_id' => (string) fake()->numberBetween(1, 999999),
            'student_name' => $name,
            'student_id_number' => fake()->unique()->numerify('2024-#####'),
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
            'year_level' => '3rd Year',
        ];
    }
}
