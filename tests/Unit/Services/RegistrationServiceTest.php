<?php

namespace Tests\Unit\Services;

use App\Models\RegistrationPin;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RegistrationService;
    }

    public function test_verify_pin_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
        ]);

        $result = $this->service->verifyPin('abc123');

        $this->assertTrue($result['ok']);
        $this->assertEquals(User::ROLE_CLERK, $result['role']);
        $this->assertEquals('Data Entry', $result['role_label']);
    }

    public function test_verify_pin_throws_validation_exception_if_not_found(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->verifyPin('XYZ999');
    }

    public function test_reserve_pin_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
        ]);

        $this->service->reservePin('ABC123', 'test@example.com', 'firebase-uid-123');

        $pin->refresh();
        $this->assertEquals('test@example.com', $pin->reserved_for_email);
        $this->assertEquals('firebase-uid-123', $pin->reserved_for_uid);
        $this->assertNotNull($pin->reserved_at);
    }

    public function test_reserve_pin_throws_if_already_reserved(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
            'reserved_at' => now(),
            'reserved_for_email' => 'other@example.com',
            'reserved_for_uid' => 'other-uid',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->reservePin('ABC123', 'test@example.com', 'firebase-uid-123');
    }

    public function test_create_user_with_pin_code_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_MANAGER,
        ]);

        $user = $this->service->createUserWithPin(
            uid: 'firebase-uid-123',
            email: 'test@example.com',
            displayName: 'Test User',
            picture: 'https://example.com/pic.jpg',
            pinCode: 'ABC123'
        );

        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('firebase-uid-123', $user->firebase_uid);
        $this->assertEquals(User::ROLE_MANAGER, $user->role);

        $pin->refresh();
        $this->assertEquals($user->id, $pin->used_by);
        $this->assertNotNull($pin->used_at);
    }

    public function test_create_user_with_reserved_pin_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_ADMIN,
            'reserved_for_email' => 'test@example.com',
            'reserved_for_uid' => 'firebase-uid-123',
            'reserved_at' => now(),
        ]);

        $user = $this->service->createUserWithPin(
            uid: 'firebase-uid-123',
            email: 'test@example.com',
            displayName: 'Test Admin',
            picture: null,
            pinCode: null
        );

        $this->assertEquals(User::ROLE_ADMIN, $user->role);
        $pin->refresh();
        $this->assertEquals($user->id, $pin->used_by);
    }

    public function test_create_user_with_pin_throws_if_no_pin_and_new_user(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no_pin');

        $this->service->createUserWithPin(
            uid: 'firebase-uid-123',
            email: 'test@example.com',
            displayName: 'Test Admin',
            picture: null,
            pinCode: null
        );
    }

    public function test_create_user_succeeds_without_pin_if_existing_user(): void
    {
        $existing = User::create([
            'display_name' => 'Existing User',
            'email' => 'test@example.com',
            'role' => User::ROLE_CLERK,
            'firebase_uid' => 'firebase-uid-123',
        ]);

        $user = $this->service->createUserWithPin(
            uid: 'firebase-uid-123',
            email: 'test@example.com',
            displayName: 'Updated Name',
            picture: null,
            pinCode: null
        );

        $this->assertEquals($existing->id, $user->id);
        $this->assertEquals('Updated Name', $user->display_name);
        $this->assertEquals(User::ROLE_CLERK, $user->role);
    }

    public function test_create_invite_success(): void
    {
        Mail::fake();
        $admin = User::create([
            'display_name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'firebase_uid' => 'admin-uid',
        ]);

        $result = $this->service->createInvite(
            role: User::ROLE_MANAGER,
            note: 'Invite note',
            sentToEmail: 'invitee@example.com',
            expiresIn: '24h',
            adminId: $admin->id
        );

        $this->assertNotNull($result['pin']);
        $this->assertEquals(User::ROLE_MANAGER, $result['role']);

        $pin = RegistrationPin::where('code', $result['pin'])->first();
        $this->assertNotNull($pin);
        $this->assertEquals('invitee@example.com', $pin->sent_to_email);
        $this->assertEquals('Invite note', $pin->note);
        $this->assertNotNull($pin->expires_at);
        $this->assertTrue($pin->expires_at->isFuture());
    }

    public function test_revoke_invite_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
        ]);

        $this->service->revokeInvite($pin);

        $pin->refresh();
        $this->assertNotNull($pin->revoked_at);
        $this->assertFalse($pin->isAvailable());
    }

    public function test_resend_invite_success(): void
    {
        Mail::fake();

        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
            'sent_to_email' => 'old@example.com',
            'link_token' => 'old-token',
            'link_opened_at' => now(),
        ]);

        $result = $this->service->resendInvite($pin, 'new@example.com');

        $pin->refresh();
        $this->assertEquals('new@example.com', $pin->sent_to_email);
        $this->assertNull($pin->link_opened_at);
        $this->assertNotEquals('old-token', $pin->link_token);
        $this->assertEquals($result['invite_url'], route('register.invite', $pin->link_token));
    }

    public function test_open_invite_success(): void
    {
        $pin = RegistrationPin::create([
            'code' => 'ABC123',
            'role' => User::ROLE_CLERK,
            'link_token' => 'secret-token',
        ]);

        $code = $this->service->openInvite('secret-token');

        $this->assertEquals('ABC123', $code);
        $pin->refresh();
        $this->assertNotNull($pin->link_opened_at);
    }
}
