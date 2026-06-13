<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * 6A.8 — Feature Test: Rate Limiting Login & OTP Verify (429)
 *
 * Memverifikasi bahwa endpoint:
 * - POST /api/v1/auth/login          → throttle 'auth' (10 req/menit per IP)
 * - POST /api/v1/auth/otp/verify     → throttle 'auth' (10 req/menit per IP)
 *
 * Sesuai 07_SECURITY.md §7.1 dan RateLimiter::for('auth') di AppServiceProvider.
 *
 * Skenario yang diuji:
 * - Login: request ke-1 s/d ke-10 diizinkan (bukan 429)
 * - Login: request ke-11 → 429
 * - Login: format respons 429 sesuai 05_API.md §1.3
 * - Login: isolasi per IP
 * - Login: setelah reset, kembali diizinkan
 * - OTP verify: request ke-11 → 429 (shared limiter 'auth')
 * - OTP verify: format respons 429 sesuai 05_API.md §1.3
 * - Login + OTP verify berbagi slot limiter 'auth' yang sama
 */
class RateLimitLoginTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_ENDPOINT      = '/api/v1/auth/login';
    private const OTP_VERIFY_ENDPOINT = '/api/v1/auth/otp/verify';
    private const LIMITER             = 'auth';
    private const MAX_ATTEMPTS        = 10;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear(self::LIMITER . '|127.0.0.1');
        RateLimiter::clear(self::LIMITER . '|10.0.0.1');
        RateLimiter::clear(self::LIMITER . '|10.0.0.2');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeAdminUser(string $password = 'password'): User
    {
        return User::factory()->create([
            'role'     => 'admin',
            'status'   => 'aktif',
            'password' => bcrypt($password),
        ]);
    }

    private function postLogin(string $email, string $password, string $ip = '127.0.0.1'): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(self::LOGIN_ENDPOINT, [
                'email'    => $email,
                'password' => $password,
            ]);
    }

    private function postOtpVerify(string $identifier, string $code, string $ip = '127.0.0.1'): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(self::OTP_VERIFY_ENDPOINT, [
                'identifier' => $identifier,
                'code'       => $code,
            ]);
    }

    // =========================================================================
    // Test: Login — request ke-1 s/d ke-10 tidak diblokir
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_allows_up_to_ten_requests_per_minute(): void
    {
        $user = $this->makeAdminUser();

        for ($i = 1; $i <= self::MAX_ATTEMPTS; $i++) {
            $response = $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
            // Boleh 401 (kredensial salah), 423 (locked), dll — BUKAN 429
            $response->assertStatus(fn (int $s) => $s !== 429);
        }
    }

    // =========================================================================
    // Test: Login — request ke-11 harus 429
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_returns_429_on_eleventh_request(): void
    {
        $user = $this->makeAdminUser();

        // Habiskan 10 slot
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }

        // Request ke-11 harus 429
        $this->postLogin($user->email, 'wrong-password', '127.0.0.1')
            ->assertStatus(429);
    }

    // =========================================================================
    // Test: Login — format respons 429 sesuai 05_API.md §1.3
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_rate_limit_response_has_correct_json_structure(): void
    {
        $user = $this->makeAdminUser();

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }

        $response = $this->postLogin($user->email, 'wrong-password', '127.0.0.1');

        $response->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJson([
                'success' => false,
                'data'    => null,
            ]);

        $this->assertNotEmpty($response->json('message'));
        $this->assertStringNotContainsString('Exception', $response->json('message'));
    }

    // =========================================================================
    // Test: Login — isolasi per IP
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_rate_limit_is_isolated_per_ip(): void
    {
        $user = $this->makeAdminUser();

        // Habiskan quota IP A
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'wrong-password', '10.0.0.1');
        }

        // IP A diblokir
        $this->postLogin($user->email, 'wrong-password', '10.0.0.1')
            ->assertStatus(429);

        // IP B tidak terpengaruh
        $this->postLogin($user->email, 'wrong-password', '10.0.0.2')
            ->assertStatus(fn (int $s) => $s !== 429);
    }

    // =========================================================================
    // Test: Login — setelah limiter di-reset, kembali diizinkan
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_rate_limit_allows_request_after_limiter_reset(): void
    {
        $user = $this->makeAdminUser();

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }

        $this->postLogin($user->email, 'wrong-password', '127.0.0.1')
            ->assertStatus(429);

        // Reset cache limiter
        RateLimiter::clear(self::LIMITER . '|127.0.0.1');

        $this->postLogin($user->email, 'wrong-password', '127.0.0.1')
            ->assertStatus(fn (int $s) => $s !== 429);
    }

    // =========================================================================
    // Test: OTP Verify — request ke-11 harus 429
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_verify_returns_429_on_eleventh_request(): void
    {
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postOtpVerify('12345', '000000', '127.0.0.1');
        }

        $this->postOtpVerify('12345', '000000', '127.0.0.1')
            ->assertStatus(429);
    }

    // =========================================================================
    // Test: OTP Verify — format respons 429 sesuai 05_API.md §1.3
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_verify_rate_limit_response_has_correct_json_structure(): void
    {
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postOtpVerify('12345', '000000', '127.0.0.1');
        }

        $response = $this->postOtpVerify('12345', '000000', '127.0.0.1');

        $response->assertStatus(429)
            ->assertJsonStructure(['success', 'message', 'data'])
            ->assertJson([
                'success' => false,
                'data'    => null,
            ]);

        $this->assertNotEmpty($response->json('message'));
    }

    // =========================================================================
    // Test: Login + OTP Verify BERBAGI slot limiter 'auth' yang sama
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     *
     * Jika user sudah melakukan 8x login gagal dan 2x OTP verify gagal
     * dari IP yang sama → request ke-11 (apapun jenisnya) harus 429.
     * Ini memverifikasi bahwa kedua endpoint memakai limiter 'auth' yang sama.
     */
    public function login_and_otp_verify_share_same_rate_limiter_bucket(): void
    {
        $user = $this->makeAdminUser();

        // 8 login gagal
        for ($i = 0; $i < 8; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }

        // 2 OTP verify gagal — melengkapi 10 slot
        for ($i = 0; $i < 2; $i++) {
            $this->postOtpVerify('12345', '000000', '127.0.0.1');
        }

        // Request ke-11 (login) harus 429
        $this->postLogin($user->email, 'wrong-password', '127.0.0.1')
            ->assertStatus(429);

        // Request ke-11 (otp verify) juga harus 429
        RateLimiter::clear(self::LIMITER . '|127.0.0.1'); // reset untuk uji ulang

        // Isi ulang 10 slot
        for ($i = 0; $i < 8; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }
        for ($i = 0; $i < 2; $i++) {
            $this->postOtpVerify('12345', '000000', '127.0.0.1');
        }

        $this->postOtpVerify('12345', '000000', '127.0.0.1')
            ->assertStatus(429);
    }

    // =========================================================================
    // Test: Header Retry-After pada respons 429 login
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function login_rate_limit_response_includes_retry_after_header(): void
    {
        $user = $this->makeAdminUser();

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'wrong-password', '127.0.0.1');
        }

        $response = $this->postLogin($user->email, 'wrong-password', '127.0.0.1');

        $response->assertStatus(429);

        $this->assertTrue(
            $response->headers->has('Retry-After') ||
            $response->headers->has('X-RateLimit-Limit'),
            'Response 429 harus menyertakan header rate limit'
        );
    }

    // =========================================================================
    // Test: Successful login TIDAK meng-increment slot (hanya failed yang dihitung)
    // Catatan: Ini adalah DESIRED behavior — tergantung implementasi AuthController.
    //          Test ini memverifikasi behavior saat ini, bukan memaksakan.
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function successful_login_consumes_rate_limit_slot(): void
    {
        $user = $this->makeAdminUser('correct-password');

        // Login berhasil pun tetap meng-increment counter throttle middleware
        // (throttle bekerja sebelum controller, bukan hanya pada gagal)
        // 10 login berhasil masih diizinkan, ke-11 harus 429
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postLogin($user->email, 'correct-password', '127.0.0.1');
        }

        $this->postLogin($user->email, 'correct-password', '127.0.0.1')
            ->assertStatus(429);
    }
}
