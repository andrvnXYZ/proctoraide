<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/student/dashboard')->assertRedirect('/login');
        $this->get('/proctor/dashboard')->assertRedirect('/login');
    }

    public function test_students_are_redirected_to_the_student_dashboard()
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertRedirect(route('student.dashboard'));

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk();
    }

    public function test_proctors_are_redirected_to_the_proctor_dashboard()
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->get('/dashboard')
            ->assertRedirect(route('proctor.dashboard'));

        $this->actingAs($proctor)
            ->get('/proctor/dashboard')
            ->assertOk();
    }

    public function test_students_cannot_access_the_proctor_dashboard()
    {
        $this->actingAs(User::factory()->create())
            ->get('/proctor/dashboard')
            ->assertForbidden();
    }

    public function test_proctors_cannot_access_the_student_dashboard()
    {
        $this->actingAs(User::factory()->proctor()->create())
            ->get('/student/dashboard')
            ->assertForbidden();
    }
}
