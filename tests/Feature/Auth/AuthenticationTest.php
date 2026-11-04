<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_students_can_authenticate_with_email_and_role()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_email_login_is_case_insensitive()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => strtoupper($user->email),
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_students_can_authenticate_with_student_id_and_role()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'identifier' => $user->student_id_number,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_proctors_can_authenticate_with_email_and_role()
    {
        $user = User::factory()->proctor()->create();

        $response = $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'password',
            'role' => User::ROLE_PROCTOR,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('proctor.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_the_wrong_role()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => User::ROLE_PROCTOR,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_unknown_identifiers_are_rejected()
    {
        $response = $this->post('/login', [
            'identifier' => 'does-not-exist@example.com',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_admin_role_cannot_be_used_to_log_in()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');
    }

    public function test_login_is_rate_limited_after_five_failed_attempts()
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'role' => User::ROLE_STUDENT,
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
