<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * AuthServiceTest
 * Unit tests untuk App\Services\AuthService
 * Referensi: 07_SECURITY.md §3 & §5
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();

        config([
            'tracer.login.max_attempts'    => 5,
            'tracer.login.lockout_minutes' => 15,
        ]);
    }

    // -------------------------------------------------------------------------
    // loginAdmin
    // -------------------------------------------------------------------------

    /** @test */
    public function login_admin_returns_token_and_user_data_on_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@unisya.ac.id',
            'password' => Hash::make('password123'),
            'role'     => 'superadmin',
            'is_active' => true,
        ]);

        $result = $this->service->loginAdmin([
            'email'    => 'admin@unisya.ac.id',
            'password' => 'password123',
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals($user->id, $result['user']['id']);
        $this->assertEquals('superadmin', $result['user']['role']);
    }

    /** @test */
    public function login_admin_throws_invalid_credentials_on_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'admin2@unisya.ac.id',
            'password' => Hash::make('correct-password'),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('INVALID_CREDENTIALS');

        $this->service->loginAdmin([
            'email'    => 'admin2@unisya.ac.id',
            'password' => 'wrong-password',
        ]);
    }

    /** @test */
    public function login_admin_throws_locked_exception_when_account_is_locked(): void
    {
        $user = User::factory()->create([
            'email'           => 'locked@unisya.ac.id',
            'password'        => Hash::make('password123'),
            'login_attempts'  => 5,
            'locked_until'    => now()->addMinutes(10),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/^LOCKED:.+$/');

        $this->service->loginAdmin([
            'email'    => 'locked@unisya.ac.id',
            'password' => 'password123',
        ]);
    }

    /** @test */
    public function login_admin_resets_login_attempts_after_successful_login(): void
    {
        $user = User::factory()->create([
            'email'          => 'admin3@unisya.ac.id',
            'password'       => Hash::make('password123'),
            'login_attempts' => 3,
            'is_active'      => true,
        ]);

        $this->service->loginAdmin([
            'email'    => 'admin3@unisya.ac.id',
            'password' => 'password123',
        ]);

        $this->assertEquals(0, $user->fresh()->login_attempts);
    }

    /** @test */
    public function login_admin_records_audit_log_on_successful_login(): void
    {
        User::factory()->create([
            'email'     => 'admin4@unisya.ac.id',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->service->loginAdmin([
            'email'    => 'admin4@unisya.ac.id',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'login',
            'auditable_type' => 'Auth',
        ]);
    }

    /** @test */
    public function login_admin_records_audit_log_on_failed_login(): void
    {
        User::factory()->create([
            'email'    => 'admin5@unisya.ac.id',
            'password' => Hash::make('correct'),
        ]);

        try {
            $this->service->loginAdmin([
                'email'    => 'admin5@unisya.ac.id',
                'password' => 'wrong',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'login_failed',
            'auditable_type' => 'Auth',
        ]);
    }

    /** @test */
    public function login_admin_increments_login_attempts_on_failed_login(): void
    {
        $user = User::factory()->create([
            'email'          => 'admin6@unisya.ac.id',
            'password'       => Hash::make('correct'),
            'login_attempts' => 0,
        ]);

        try {
            $this->service->loginAdmin([
                'email'    => 'admin6@unisya.ac.id',
                'password' => 'wrong',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertGreaterThan(0, $user->fresh()->login_attempts);
    }

    // -------------------------------------------------------------------------
    // loginViaEmployerToken
    // -------------------------------------------------------------------------

    /** @test */
    public function login_via_employer_token_returns_token_and_employer_data(): void
    {
        $user = User::factory()->create(['role' => 'employer']);
        $employer = Employer::factory()->create([
            'user_id'       => $user->id,
            'survey_token'  => 'valid-token-abc123',
            'token_expires_at' => now()->addDays(30),
        ]);

        $result = $this->service->loginViaEmployerToken($employer);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('employer', $result);
        $this->assertArrayHasKey('survey_url', $result);
        $this->assertEquals($employer->id, $result['employer']['id']);
    }

    /** @test */
    public function login_via_employer_token_throws_when_employer_has_no_user(): void
    {
        $employer = Employer::factory()->create([
            'user_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('EMPLOYER_USER_NOT_FOUND');

        $this->service->loginViaEmployerToken($employer);
    }

    // -------------------------------------------------------------------------
    // logout
    // -------------------------------------------------------------------------

    /** @test */
    public function logout_deletes_current_access_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web');
        $this->actingAs($user, 'sanctum');

        // Refresh user agar currentAccessToken() tersedia
        $authenticatedUser = User::find($user->id);
        // Simulasi: set token via actingAs tidak men-set currentAccessToken,
        // gunakan plainTextToken untuk lookup
        $tokenModel = $authenticatedUser->tokens()->where('name', 'web')->first();
        $this->assertNotNull($tokenModel);

        // Panggil logout dengan user yang memiliki token aktif
        // (set current token via withAccessToken)
        $authenticatedUser->withAccessToken($tokenModel);
        $this->service->logout($authenticatedUser);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenModel->id,
        ]);
    }

    /** @test */
    public function logout_records_audit_log(): void
    {
        $user = User::factory()->create();
        $tokenModel = $user->createToken('web');
        $user->withAccessToken($user->tokens()->first());

        $this->service->logout($user);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'logout',
            'auditable_type' => 'Auth',
        ]);
    }
}
