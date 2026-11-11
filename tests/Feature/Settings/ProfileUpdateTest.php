<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_incomplete_email_addresses_are_rejected()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'asdasasss@gma',
            ]);

        $response
            ->assertSessionHasErrors('email')
            ->assertRedirect('/settings/profile');

        $this->assertNotSame('asdasasss@gma', $user->refresh()->email);
    }

    public function test_profile_updates_normalize_the_email_address()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => '  New.Address@Example.COM ',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('new.address@example.com', $user->refresh()->email);
    }

    public function test_profile_updates_cannot_change_privileged_fields()
    {
        $user = User::factory()->create();
        $originalRole = $user->role;
        $originalStudentId = $user->student_id_number;

        $this->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'role' => User::ROLE_ADMIN,
                'student_id_number' => 'HACKED-0001',
                'email_verified_at' => now(),
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame($originalRole, $user->role);
        $this->assertSame($originalStudentId, $user->student_id_number);
    }
}
