<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * 6A.7 — Feature Test: Rate Limiting OTP Request (429)
 *
 * Memverifikasi bahwa endpoint POST /api/v1/auth/otp/request
 * memberlakukan throttle 5 req/menit per IP sesuai 07_SECURITY.md §7.1
 * dan RateLimiter::for('otp-request') di AppServiceProvider.
 *
 * Skenario yang diuji:
 * - Request pertama s/d ke-5 : 200 (allowed)
 * - Request ke-6 dst.        : 429 (blocked)
 * - Format respons 429        : { success, message, data }
 * - Cache key berbeda per IP  : IP berbeda tidak saling mengunci
 * - Setelah limiter di-reset  : request kembali allowed
 */
class RateLimitOtpRequestTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/auth/otp/request';
    private const LIMITER  = 'otp-request';

    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan limiter bersih sebelum setiap test
        RateLimiter::clear(self::LIMITER . '|127.0.0.1');
        RateLimiter::clear(self::LIMITER . '|10.0.0.1');
        RateLimiter::clear(self::LIMITER . '|10.0.0.2');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Buat alumni user + OTP code entry sehingga OTP request endpoint bisa
     * berjalan tanpa error domain (bukan rate limiting).
     */
    private function makeAlumniUserWithPhone(string $phone = '628100000001'): User
    {
        return User::factory()->create([
            'role'   => 'alumni',
            'status' => 'aktif',
        ])->tap(function (User $user) use ($phone) {
            $user->alumni()->create([
                'nim'   => '12345',
                'name'  => 'Test Alumni',
                'phone' => $phone,
                'email' => $user->email,
            ]);
        });
    }

    /**
     * Kirim request OTP dengan identifier (NIM / phone / email)
     * dari IP yang ditentukan.
     */
    private function requestOtp(string $identifier, string $ip = '127.0.0.1'): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(self::ENDPOINT, [
                'identifier' => $identifier,
                'channel'    => 'whatsapp',
            ]);
    }

    // =========================================================================
    // Test: Request ke-1 s/d ke-5 diizinkan (2xx atau 4xx selain 429)
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_request_allows_up_to_five_requests_per_minute(): void
    {
        $this->makeAlumniUserWithPhone();

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->requestOtp('12345', '127.0.0.1');

            // Boleh 200 (sukses kirim OTP) atau error domain lainnya,
            // yang penting BUKAN 429
            $response->assertStatus(fn (int $status) => $status !== 429);
        }
    }

    // =========================================================================
    // Test: Request ke-6 harus 429
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_request_returns_429_on_sixth_request(): void
    {
        $this->makeAlumniUserWithPhone();

        // Habiskan 5 slot
        for ($i = 0; $i < 5; $i++) {
            $this->requestOtp('12345', '127.0.0.1');
        }

        // Request ke-6 harus 429
        $response = $this->requestOtp('12345', '127.0.0.1');
        $response->assertStatus(429);
    }

    // =========================================================================
    // Test: Format respons 429 sesuai 05_API.md §1.3
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_rate_limit_response_has_correct_json_structure(): void
    {
        $this->makeAlumniUserWithPhone();

        // Habiskan quota
        for ($i = 0; $i < 5; $i++) {
            $this->requestOtp('12345', '127.0.0.1');
        }

        $response = $this->requestOtp('12345', '127.0.0.1');

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => false,
                'data'    => null,
            ]);

        // Pastikan message tidak kosong dan bukan stack trace
        $this->assertNotEmpty($response->json('message'));
        $this->assertStringNotContainsString('Exception', $response->json('message'));
        $this->assertStringNotContainsString('Trace', $response->json('message'));
    }

    // =========================================================================
    // Test: Isolasi per IP — IP berbeda tidak saling mengunci
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_rate_limit_is_isolated_per_ip(): void
    {
        $this->makeAlumniUserWithPhone();

        // Habiskan quota untuk IP A
        for ($i = 0; $i < 5; $i++) {
            $this->requestOtp('12345', '10.0.0.1');
        }

        // IP A harus diblokir
        $this->requestOtp('12345', '10.0.0.1')
            ->assertStatus(429);

        // IP B tetap bisa request (bukan 429)
        $this->requestOtp('12345', '10.0.0.2')
            ->assertStatus(fn (int $s) => $s !== 429);
    }

    // =========================================================================
    // Test: Setelah limiter di-reset, request kembali diizinkan
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_rate_limit_allows_request_after_limiter_reset(): void
    {
        $this->makeAlumniUserWithPhone();

        // Habiskan quota
        for ($i = 0; $i < 5; $i++) {
            $this->requestOtp('12345', '127.0.0.1');
        }

        // Blokir terkonfirmasi
        $this->requestOtp('12345', '127.0.0.1')
            ->assertStatus(429);

        // Simulasi TTL habis — reset cache limiter
        RateLimiter::clear(self::LIMITER . '|127.0.0.1');

        // Setelah reset, tidak boleh 429
        $this->requestOtp('12345', '127.0.0.1')
            ->assertStatus(fn (int $s) => $s !== 429);
    }

    // =========================================================================
    // Test: Header Retry-After ada pada respons 429
    // =========================================================================

    /**
     * @test
     * @group rate-limiting
     */
    public function otp_rate_limit_response_includes_retry_after_header(): void
    {
        $this->makeAlumniUserWithPhone();

        for ($i = 0; $i < 5; $i++) {
            $this->requestOtp('12345', '127.0.0.1');
        }

        $response = $this->requestOtp('12345', '127.0.0.1');

        $response->assertStatus(429);

        // Laravel secara otomatis menambahkan X-RateLimit-* dan Retry-After
        // saat menggunakan ThrottleRequests middleware bawaan
        $this->assertTrue(
            $response->headers->has('Retry-After') ||
            $response->headers->has('X-RateLimit-Limit'),
            'Response 429 harus menyertakan header rate limit'
        );
    }
}
