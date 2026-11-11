<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_students_can_register()
    {
        $response = $this->post('/register', $this->registrationPayload());

        $response->assertRedirect(route('student.dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'juan.delacruz@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Juan', $user->first_name);
        $this->assertSame('Cruz', $user->last_name);
        $this->assertSame('2024-00001', $user->student_id_number);
        $this->assertSame(User::ROLE_STUDENT, $user->role);
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertNotSame('Password123!', $user->password);
        $this->assertArrayNotHasKey('confirm_password', $user->getAttributes());
        $this->assertNotNull($user->birthday);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_registration_accepts_confirm_password_alias()
    {
        $payload = $this->registrationPayload();
        unset($payload['password_confirmation']);
        $payload['confirm_password'] = $payload['password'];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('student.dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_proctors_can_register_without_student_fields()
    {
        $payload = $this->registrationPayload([
            'email' => 'proctor@example.com',
            'student_id_number' => null,
            'year_level' => null,
            'role' => User::ROLE_PROCTOR,
        ]);

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('proctor.dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'proctor@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_PROCTOR, $user->role);
        $this->assertNull($user->student_id_number);
        $this->assertNull($user->year_level);
    }

    public function test_duplicate_email_addresses_cannot_register()
    {
        User::factory()->create([
            'email' => 'juan.delacruz@example.com',
            'student_id_number' => '2024-00009',
        ]);

        $response = $this->post('/register', $this->registrationPayload([
            'student_id_number' => '2024-00002',
            'email' => 'juan.delacruz@example.com',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'juan.delacruz@example.com')->count());
    }

    public function test_duplicate_student_id_numbers_cannot_register()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'student_id_number' => '2024-00001',
        ]);

        $response = $this->post('/register', $this->registrationPayload([
            'email' => 'another@example.com',
            'student_id_number' => '2024-00001',
        ]));

        $response->assertSessionHasErrors('student_id_number');
        $this->assertSame(1, User::where('student_id_number', '2024-00001')->count());
    }

    public function test_users_cannot_register_as_admin()
    {
        $response = $this->post('/register', $this->registrationPayload([
            'role' => User::ROLE_ADMIN,
        ]));

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_required_registration_fields_are_validated()
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'birthday',
            'college',
            'department',
            'email',
            'password',
            'role',
        ]);

        $this->assertSame(0, User::count());
    }

    public function test_terms_must_be_accepted()
    {
        $payload = $this->registrationPayload();
        unset($payload['terms_accepted']);

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors('terms_accepted_at');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_incomplete_email_addresses_cannot_register()
    {
        foreach (['asdasasss@gma', 'student@example', 'student example.com', 'student@@example.com'] as $invalidEmail) {
            $response = $this->post('/register', $this->registrationPayload([
                'email' => $invalidEmail,
            ]));

            $response->assertSessionHasErrors('email');
        }

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_weak_passwords_cannot_register()
    {
        $response = $this->post('/register', $this->registrationPayload([
            'password' => '1234567i9',
            'password_confirmation' => '1234567i9',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_email_addresses_are_normalized_before_storage()
    {
        $response = $this->post('/register', $this->registrationPayload([
            'email' => '  Juan.DelaCruz@Example.COM  ',
        ]));

        $response->assertRedirect(route('student.dashboard', absolute: false));

        $this->assertNotNull(User::where('email', 'juan.delacruz@example.com')->first());
    }

    public function test_duplicate_email_addresses_are_detected_case_insensitively()
    {
        User::factory()->create([
            'email' => 'juan.delacruz@example.com',
            'student_id_number' => '2024-00009',
        ]);

        $response = $this->post('/register', $this->registrationPayload([
            'student_id_number' => '2024-00002',
            'email' => 'JUAN.DELACRUZ@EXAMPLE.COM',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'juan.delacruz@example.com')->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'birthday' => '2000-01-15',
            'student_id_number' => '2024-00001',
            'college' => 'College of Engineering',
            'department' => 'Computer Science',
            'year_level' => '3rd Year',
            'email' => 'juan.delacruz@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => User::ROLE_STUDENT,
            'terms_accepted' => true,
        ], $overrides);
    }
}
