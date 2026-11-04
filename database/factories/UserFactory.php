<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'birthday' => fake()->dateTimeBetween('-30 years', '-16 years')->format('Y-m-d'),
            'student_id_number' => fake()->unique()->numerify('2024-#####'),
            'college' => fake()->randomElement(['College of Engineering', 'College of Education', 'College of Science']),
            'department' => fake()->randomElement(['Computer Science', 'Information Technology', 'Mathematics']),
            'year_level' => fake()->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'terms_accepted_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a proctor.
     */
    public function proctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_PROCTOR,
            'student_id_number' => null,
            'year_level' => null,
        ]);
    }
}
