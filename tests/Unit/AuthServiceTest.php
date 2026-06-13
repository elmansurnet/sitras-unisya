<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 6A.13 — Unit Test: AuthService
 *
 * Cakupan test:
 * A. loginAdmin()
 *    - Return array { token, token_type, user } saat berhasil
 *    - token_type selalu 'Bearer'
 *    - Reset failed_login_attempts setelah berhasil
 *    - Update last_login_at setelah berhasil
 *    - Catat audit_log event 'login' setelah berhasil
 *    - Throw INVALID_CREDENTIALS saat password salah
 *    - Increment failed_login_attempts saat gagal
 *    - Catat audit_log event 'login_failed' saat gagal
 *    - Throw LOCKED saat akun terkunci
 *    - Catat audit_log event 'login_failed' dengan reason account_locked
 *
 * B. loginViaEmployerToken()
 *    - Return array { token, token_type, employer, survey_url } saat berhasil
 *    - Throw EMPLOYER_USER_NOT_FOUND jika employer tidak punya user
 *    - Catat audit_log event 'login' dengan via=employer_token
 *
 * C. logout()
 *    - Hapus current access token
 *    - Catat audit_log event 'logout'
 */
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeAdmin(string $password = 'secret', array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role'                  => 'admin',
            'status'                => 'aktif',
            'password'              => bcrypt($password),
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ], $extra));
    }

    // =========================================================================
    // A. loginAdmin()
    // =========================================================================

    /**
     * @test
     * @group auth
     */
    public function login_admin_returns_token_on_valid_credentials(): void
    {
        $user = $this->makeAdmin('correct-password');

        $result = $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'correct-password',
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertNotEmpty($result['token']);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_token_type_is_bearer(): void
    {
        $user = $this->makeAdmin('secret');

        $result = $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);

        $this->assertEquals('Bearer', $result['token_type']);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_resets_failed_attempts_on_success(): void
    {
        $user = $this->makeAdmin('secret', ['failed_login_attempts' => 3]);

        $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);

        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_updates_last_login_at_on_success(): void
    {
        $user = $this->makeAdmin('secret');

        $before = now()->subSecond();

        $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertTrue($user->fresh()->last_login_at->isAfter($before));
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_records_audit_log_on_success(): void
    {
        $user = $this->makeAdmin('secret');

        $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event'       => 'login',
            'auditable_type' => 'Auth',
            'user_id'     => $user->id,
        ]);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_throws_invalid_credentials_on_wrong_password(): void
    {
        $user = $this->makeAdmin('correct-password');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('INVALID_CREDENTIALS');

        $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_increments_failed_attempts_on_wrong_password(): void
    {
        $user = $this->makeAdmin('correct-password');

        try {
            $this->service->loginAdmin([
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals(1, $user->fresh()->failed_login_attempts);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_records_audit_log_on_failure(): void
    {
        $user = $this->makeAdmin('correct-password');

        try {
            $this->service->loginAdmin([
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertDatabaseHas('audit_logs', [
            'event'          => 'login_failed',
            'auditable_type' => 'Auth',
        ]);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_throws_locked_when_account_is_locked(): void
    {
        // Akun terkunci: locked_until di masa depan
        $user = $this->makeAdmin('secret', [
            'locked_until'          => now()->addMinutes(15),
            'failed_login_attempts' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/^LOCKED:/');

        $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_records_audit_log_with_account_locked_reason(): void
    {
        $user = $this->makeAdmin('secret', [
            'locked_until'          => now()->addMinutes(15),
            'failed_login_attempts' => 5,
        ]);

        try {
            $this->service->loginAdmin([
                'email'    => $user->email,
                'password' => 'secret',
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        // Harus ada audit log login_failed dengan reason account_locked
        $log = AuditLog::where('event', 'login_failed')
            ->where('auditable_type', 'Auth')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->new_values);
        $newValues = is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values;
        $this->assertEquals('account_locked', $newValues['reason'] ?? null);
    }

    /**
     * @test
     * @group auth
     */
    public function login_admin_user_data_in_result_has_required_fields(): void
    {
        $user = $this->makeAdmin('secret');

        $result = $this->service->loginAdmin([
            'email'    => $user->email,
            'password' => 'secret',
        ]);

        $this->assertArrayHasKey('id', $result['user']);
        $this->assertArrayHasKey('name', $result['user']);
        $this->assertArrayHasKey('role', $result['user']);
        $this->assertArrayHasKey('email', $result['user']);
        $this->assertArrayHasKey('last_login_at', $result['user']);
        $this->assertEquals($user->email, $result['user']['email']);
    }

    // =========================================================================
    // B. loginViaEmployerToken()
    // =========================================================================

    /**
     * @test
     * @group auth
     */
    public function login_via_employer_token_returns_expected_structure(): void
    {
        $user = User::factory()->create([
            'role'   => 'employer',
            'status' => 'aktif',
        ]);

        $employer = Employer::factory()->create([
            'user_id'                  => $user->id,
            'survey_token'             => 'valid-token-64chars',
            'survey_token_expires_at'  => now()->addDays(30),
            'survey_status'            => 'belum',
        ]);

        $result = $this->service->loginViaEmployerToken($employer);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('employer', $result);
        $this->assertArrayHasKey('survey_url', $result);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals('/employer/survey', $result['survey_url']);
    }

    /**
     * @test
     * @group auth
     */
    public function login_via_employer_token_throws_if_no_user_linked(): void
    {
        // Employer tanpa user_id
        $employer = Employer::factory()->create(['user_id' => null]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('EMPLOYER_USER_NOT_FOUND');

        $this->service->loginViaEmployerToken($employer);
    }

    /**
     * @test
     * @group auth
     */
    public function login_via_employer_token_records_audit_log(): void
    {
        $user = User::factory()->create([
            'role'   => 'employer',
            'status' => 'aktif',
        ]);

        $employer = Employer::factory()->create(['user_id' => $user->id]);

        $this->service->loginViaEmployerToken($employer);

        $log = AuditLog::where('event', 'login')
            ->where('auditable_type', 'Auth')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $newValues = is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values;
        $this->assertEquals('employer_token', $newValues['via'] ?? null);
        $this->assertEquals($employer->id, $newValues['employer_id'] ?? null);
    }

    /**
     * @test
     * @group auth
     */
    public function login_via_employer_token_result_employer_has_required_fields(): void
    {
        $user = User::factory()->create(['role' => 'employer', 'status' => 'aktif']);
        $employer = Employer::factory()->create(['user_id' => $user->id]);

        $result = $this->service->loginViaEmployerToken($employer);

        $this->assertArrayHasKey('id', $result['employer']);
        $this->assertArrayHasKey('company_name', $result['employer']);
        $this->assertArrayHasKey('contact_person_name', $result['employer']);
    }

    // =========================================================================
    // C. logout()
    // =========================================================================

    /**
     * @test
     * @group auth
     */
    public function logout_deletes_current_access_token(): void
    {
        $user = $this->makeAdmin('secret');
        $token = $user->createToken('web');
        $user->withAccessToken($token->accessToken);

        // Verifikasi token ada sebelum logout
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        $this->service->logout($user);

        // Token harus sudah dihapus
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /**
     * @test
     * @group auth
     */
    public function logout_records_audit_log(): void
    {
        $user = $this->makeAdmin('secret');
        $token = $user->createToken('web');
        $user->withAccessToken($token->accessToken);

        $this->service->logout($user);

        $this->assertDatabaseHas('audit_logs', [
            'event'          => 'logout',
            'auditable_type' => 'Auth',
            'user_id'        => $user->id,
        ]);
    }
}
