<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshMongoDatabase;

    private const STRONG_PASSWORD = 'StrongerPass123';

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_can_be_requested_with_a_student_id_number()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['identifier' => $user->student_id_number]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => self::STRONG_PASSWORD,
                'password_confirmation' => self::STRONG_PASSWORD,
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_forgot_password_response_does_not_reveal_whether_an_account_exists()
    {
        Notification::fake();

        $user = User::factory()->create();

        $existing = $this->post('/forgot-password', ['email' => $user->email]);
        $missing = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $expected = 'If an account matches that information, a reset link has been sent.';

        $existing->assertSessionHasNoErrors()->assertSessionHas('status', $expected);
        $missing->assertSessionHasNoErrors()->assertSessionHas('status', $expected);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_incomplete_email_addresses_are_rejected()
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['identifier' => 'asdasasss@gma']);

        $response->assertSessionHasErrors('identifier');
        Notification::assertNothingSent();
    }

    public function test_reset_token_cannot_be_reused()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $payload = [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => self::STRONG_PASSWORD,
                'password_confirmation' => self::STRONG_PASSWORD,
            ];

            $this->post('/reset-password', $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $this->post('/reset-password', $payload)
                ->assertSessionHasErrors('email');

            return true;
        });

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_expired_reset_tokens_are_rejected()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->travel(16)->minutes();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => self::STRONG_PASSWORD,
                'password_confirmation' => self::STRONG_PASSWORD,
            ])->assertSessionHasErrors([
                'email' => 'This password reset link is invalid or has expired. Please request a new one.',
            ]);

            return true;
        });
    }

    public function test_weak_passwords_cannot_be_used_when_resetting()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => '1234567i9',
                'password_confirmation' => '1234567i9',
            ])->assertSessionHasErrors('password');

            return true;
        });
    }

    public function test_reset_failures_use_one_generic_message()
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ])->assertSessionHasErrors([
            'email' => 'This password reset link is invalid or has expired. Please request a new one.',
        ]);
    }

    public function test_reset_password_requests_are_rate_limited()
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(429);
    }
}
