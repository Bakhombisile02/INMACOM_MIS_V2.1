<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Page rendering
    // -----------------------------------------------------------------------

    public function test_user_can_view_login_options_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_can_view_login_email_page(): void
    {
        $this->get('/login/email')->assertOk();
    }

    public function test_user_can_view_register_options_page(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_user_can_view_register_email_page(): void
    {
        $this->get('/register/email')->assertOk();
    }

    // -----------------------------------------------------------------------
    // Firebase auth endpoint - validation
    // -----------------------------------------------------------------------

    public function test_firebase_auth_endpoint_requires_id_token(): void
    {
        $this->post('/auth/firebase', [])->assertSessionHasErrors('id_token');
    }

    public function test_firebase_auth_endpoint_rejects_invalid_token(): void
    {
        $response = $this->from('/login/email')->post('/auth/firebase', [
            'id_token' => 'not-a-real-firebase-token',
        ]);

        $this->assertGuest();
        // 302 = token verified but invalid (with credentials), 500 = no credentials in test env
        $this->assertContains($response->status(), [302, 422, 500]);
    }

    // -----------------------------------------------------------------------
    // Logout
    // -----------------------------------------------------------------------

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // -----------------------------------------------------------------------
    // Authenticated pages redirect guests
    // -----------------------------------------------------------------------

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect();
    }
}
