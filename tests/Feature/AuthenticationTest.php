<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = $this->makeUser('employee');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'login']);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_passwords_are_hashed(): void
    {
        $user = $this->makeUser('employee');
        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser('employee');
        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_accounts_cannot_log_in(): void
    {
        $user = $this->makeUser('employee');
        $user->update(['status' => 'disabled']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_user_is_logged_out_on_next_request(): void
    {
        $user = $this->makeUser('employee');
        $this->actingAs($user);
        $user->update(['status' => 'disabled']);

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->makeUser('employee');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'bad']);
        }
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = $this->makeUser('employee');

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.$user->email)->assertOk();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])->assertRedirect('/login');

            return Hash::check('new-secret-123', $user->fresh()->password);
        });
    }

    public function test_registration_is_disabled_by_default_and_works_when_enabled(): void
    {
        $this->get('/register')->assertNotFound();

        \App\Support\Settings::set(['registration_enabled' => '1']);
        $this->get('/register')->assertOk();
        $this->post('/register', [
            'name' => 'New Person', 'email' => 'new@example.test',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/');

        $user = User::where('email', 'new@example.test')->first();
        $this->assertSame('employee', $user->role->slug);
    }
}
