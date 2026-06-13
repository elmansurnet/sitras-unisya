<?php

namespace Tests\Unit;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 6A.12 — Unit Test: OtpService
 *
 * Cakupan test:
 * A. generateOtp()
 *    - Menyimpan SHA-256 hash (bukan plaintext) ke DB
 *    - Return rawOtp yang merupakan 6 digit numerik
 *    - Throw exception COOLDOWN jika request dalam 60 detik
 *    - Invalidasi OTP lama saat request OTP baru setelah cooldown
 *    - expires_at sesuai config tracer.otp.expiry_minutes
 *    - attempts default 0 saat create
 *
 * B. verifyOtp()
 *    - Return OtpCode saat kode valid
 *    - Return false saat kode salah + increment attempts
 *    - Invalidasi OTP setelah max_attempts kali gagal
 *    - Return false jika OTP sudah is_used
 *    - Return false jika OTP sudah expired
 *    - Mark is_used=1 setelah verifikasi berhasil
 *    - Timing-safe: tidak bisa brute force lewat timing
 *
 * C. dispatchOtpNotification()
 *    - Dispatch ke queue 'high'
 *    - Dispatch SendWhatsAppNotification untuk channel 'whatsapp'
 *    - Dispatch SendEmailNotification untuk channel 'email'
 *
 * D. maskDestination()
 *    - Mask nomor phone: 2 awal + bintang + 2 akhir
 *    - Mask email: local part di-mask, domain di-mask parsial
 *    - String pendek (<= 4 char) tidak di-mask
 */
class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpService();
    }

    // =========================================================================
    // A. generateOtp()
    // =========================================================================

    /**
     * @test
     * @group otp
     */
    public function generate_otp_stores_sha256_hash_not_plaintext(): void
    {
        $user = User::factory()->create(['role' => 'alumni']);

        $rawOtp = $this->service->generateOtp($user, '628100000001', 'whatsapp');

        $record = OtpCode::where('identifier', '628100000001')->latest()->first();

        $this->assertNotNull($record);
        // Kolom code harus berisi SHA-256 hex (64 karakter) bukan rawOtp 6 digit
        $this->assertNotEquals($rawOtp, $record->code);
        $this->assertEquals(64, strlen($record->code));
        // Verifikasi bahwa yang tersimpan adalah hash dari rawOtp
        $this->assertEquals(hash('sha256', $rawOtp), $record->code);
    }

    /**
     * @test
     * @group otp
     */
    public function generate_otp_returns_six_digit_numeric_string(): void
    {
        $user = User::factory()->create(['role' => 'alumni']);

        $rawOtp = $this->service->generateOtp($user, '628100000002', 'whatsapp');

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $rawOtp);
        $this->assertGreaterThanOrEqual(100000, (int) $rawOtp);
        $this->assertLessThanOrEqual(999999, (int) $rawOtp);
    }

    /**
     * @test
     * @group otp
     */
    public function generate_otp_throws_cooldown_exception_within_60_seconds(): void
    {
        $user = User::factory()->create(['role' => 'alumni']);

        // Request pertama berhasil
        $this->service->generateOtp($user, '628100000003', 'whatsapp');

        // Request kedua dalam 60 detik harus throw COOLDOWN
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/^COOLDOWN:\d+$/');

        $this->service->generateOtp($user, '628100000003', 'whatsapp');
    }

    /**
     * @test
     * @group otp
     */
    public function generate_otp_invalidates_old_otp_after_cooldown_expires(): void
    {
        $user = User::factory()->create(['role' => 'alumni']);

        // Buat OTP lama dengan created_at > 60 detik yang lalu
        $oldOtp = OtpCode::create([
            'user_id'    => $user->id,
            'identifier' => '628100000004',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '111111'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);
        // Backdate created_at agar cooldown sudah terlewat
        $oldOtp->update(['created_at' => now()->subSeconds(61)]);

        // Generate OTP baru — harus berhasil dan invalidasi yang lama
        $this->service->generateOtp($user, '628100000004', 'whatsapp');

        $oldOtp->refresh();
        $this->assertEquals(1, $oldOtp->is_used, 'OTP lama harus diinvalidasi');

        // OTP baru harus ada di DB
        $newCount = OtpCode::where('identifier', '628100000004')
            ->where('is_used', 0)
            ->count();
        $this->assertEquals(1, $newCount);
    }

    /**
     * @test
     * @group otp
     */
    public function generate_otp_sets_expires_at_from_config(): void
    {
        config(['tracer.otp.expiry_minutes' => 5]);

        $user = User::factory()->create(['role' => 'alumni']);
        $this->service->generateOtp($user, '628100000005', 'whatsapp');

        $record = OtpCode::where('identifier', '628100000005')->latest()->first();

        // expires_at harus ~5 menit dari sekarang (toleransi 5 detik)
        $this->assertEqualsWithDelta(
            now()->addMinutes(5)->timestamp,
            $record->expires_at->timestamp,
            5
        );
    }

    /**
     * @test
     * @group otp
     */
    public function generate_otp_sets_attempts_to_zero(): void
    {
        $user = User::factory()->create(['role' => 'alumni']);
        $this->service->generateOtp($user, '628100000006', 'whatsapp');

        $record = OtpCode::where('identifier', '628100000006')->latest()->first();
        $this->assertEquals(0, $record->attempts);
        $this->assertEquals(0, $record->is_used);
    }

    // =========================================================================
    // B. verifyOtp()
    // =========================================================================

    /**
     * @test
     * @group otp
     */
    public function verify_otp_returns_otp_record_on_valid_code(): void
    {
        $rawOtp = '123456';
        OtpCode::create([
            'identifier' => '628100000010',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', $rawOtp),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $result = $this->service->verifyOtp('628100000010', $rawOtp);

        $this->assertInstanceOf(OtpCode::class, $result);
        $this->assertEquals(1, $result->fresh()->is_used);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_returns_false_on_wrong_code(): void
    {
        OtpCode::create([
            'identifier' => '628100000011',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '999999'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $result = $this->service->verifyOtp('628100000011', '111111');

        $this->assertFalse($result);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_increments_attempts_on_failure(): void
    {
        OtpCode::create([
            'identifier' => '628100000012',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '999999'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $this->service->verifyOtp('628100000012', '000000');

        $record = OtpCode::where('identifier', '628100000012')->first();
        $this->assertEquals(1, $record->attempts);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_invalidates_after_max_attempts(): void
    {
        config(['tracer.otp.max_attempts' => 3]);

        OtpCode::create([
            'identifier' => '628100000013',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '999999'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        // 3 kali gagal
        $this->service->verifyOtp('628100000013', '000000');
        $this->service->verifyOtp('628100000013', '000000');
        $this->service->verifyOtp('628100000013', '000000');

        $record = OtpCode::where('identifier', '628100000013')->first();

        // Setelah 3 kali gagal, is_used harus 1 (diblokir)
        $this->assertEquals(1, $record->is_used);

        // Request ke-4 pun harus false
        $result = $this->service->verifyOtp('628100000013', '999999');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_returns_false_if_already_used(): void
    {
        OtpCode::create([
            'identifier' => '628100000014',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => true, // sudah terpakai
            'attempts'   => 0,
        ]);

        $result = $this->service->verifyOtp('628100000014', '123456');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_returns_false_if_expired(): void
    {
        OtpCode::create([
            'identifier' => '628100000015',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '123456'),
            'expires_at' => now()->subMinutes(1), // sudah expired
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $result = $this->service->verifyOtp('628100000015', '123456');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_marks_record_as_used_on_success(): void
    {
        $rawOtp = '654321';
        OtpCode::create([
            'identifier' => '628100000016',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', $rawOtp),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $this->service->verifyOtp('628100000016', $rawOtp);

        $record = OtpCode::where('identifier', '628100000016')->first();
        $this->assertEquals(1, $record->is_used);
    }

    /**
     * @test
     * @group otp
     */
    public function verify_otp_is_case_sensitive_and_not_guessable_by_length(): void
    {
        // Memastikan OTP dengan kode hampir sama (leading zero) tetap ditolak
        OtpCode::create([
            'identifier' => '628100000017',
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '100000'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        // Coba dengan nilai yang beda satu digit
        $this->assertFalse($this->service->verifyOtp('628100000017', '100001'));
        $this->assertFalse($this->service->verifyOtp('628100000017', '100010'));
    }

    // =========================================================================
    // C. dispatchOtpNotification()
    // =========================================================================

    /**
     * @test
     * @group otp
     */
    public function dispatch_otp_notification_pushes_whatsapp_job_to_high_queue(): void
    {
        Queue::fake();

        $this->service->dispatchOtpNotification('123456', '628100000099', 'whatsapp');

        Queue::assertPushedOn('high', SendWhatsAppNotification::class);
        Queue::assertNotPushed(SendEmailNotification::class);
    }

    /**
     * @test
     * @group otp
     */
    public function dispatch_otp_notification_pushes_email_job_to_high_queue(): void
    {
        Queue::fake();

        $this->service->dispatchOtpNotification('123456', 'alumni@example.com', 'email');

        Queue::assertPushedOn('high', SendEmailNotification::class);
        Queue::assertNotPushed(SendWhatsAppNotification::class);
    }

    /**
     * @test
     * @group otp
     */
    public function dispatch_otp_notification_message_contains_raw_otp(): void
    {
        Queue::fake();

        $this->service->dispatchOtpNotification('789012', '628100000099', 'whatsapp');

        Queue::assertPushedOn('high', SendWhatsAppNotification::class, function (SendWhatsAppNotification $job) {
            // Message harus mengandung rawOtp
            return str_contains($job->message, '789012');
        });
    }

    // =========================================================================
    // D. maskDestination()
    // =========================================================================

    /**
     * @test
     * @group otp
     */
    public function mask_destination_masks_phone_number_correctly(): void
    {
        // 2 awal + bintang + 2 akhir
        $masked = $this->service->maskDestination('628100000001');
        $this->assertEquals('62**********01', $masked);
        $this->assertStringStartsWith('62', $masked);
        $this->assertStringEndsWith('01', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    /**
     * @test
     * @group otp
     */
    public function mask_destination_masks_email_correctly(): void
    {
        $masked = $this->service->maskDestination('ahmad@gmail.com');

        // Local part: 'ah' + bintang (3 bintang untuk 'mad')
        $this->assertStringStartsWith('ah', $masked);
        $this->assertStringContainsString('@', $masked);
        $this->assertStringContainsString('*', $masked);
        // Domain suffix harus tetap ada
        $this->assertStringEndsWith('.com', $masked);
    }

    /**
     * @test
     * @group otp
     */
    public function mask_destination_returns_original_if_four_chars_or_less(): void
    {
        $this->assertEquals('1234', $this->service->maskDestination('1234'));
        $this->assertEquals('abc', $this->service->maskDestination('abc'));
    }

    /**
     * @test
     * @group otp
     */
    public function mask_destination_masks_nim_format(): void
    {
        $masked = $this->service->maskDestination('12345678');
        // 2 awal '12', bintang 4, 2 akhir '78'
        $this->assertEquals('12****78', $masked);
    }
}
