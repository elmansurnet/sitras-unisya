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
 * OtpServiceTest
 * Unit tests untuk App\Services\OtpService
 * Referensi: 07_SECURITY.md §4.2
 */
class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpService();

        // Override config agar test deterministik
        config([
            'tracer.otp.expiry_minutes'          => 5,
            'tracer.otp.max_attempts'            => 3,
            'tracer.otp.resend_cooldown_seconds' => 60,
        ]);
    }

    // -------------------------------------------------------------------------
    // generateOtp
    // -------------------------------------------------------------------------

    /** @test */
    public function generate_otp_returns_6_digit_numeric_string(): void
    {
        $user = User::factory()->create();

        $raw = $this->service->generateOtp($user, '087800000001', 'whatsapp');

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $raw);
    }

    /** @test */
    public function generate_otp_stores_sha256_hash_not_plaintext(): void
    {
        $user = User::factory()->create();

        $raw = $this->service->generateOtp($user, '087800000002', 'whatsapp');

        $record = OtpCode::where('identifier', '087800000002')->latest()->first();
        $this->assertNotNull($record);
        $this->assertNotEquals($raw, $record->code);
        $this->assertEquals(hash('sha256', $raw), $record->code);
        $this->assertEquals(64, strlen($record->code));
    }

    /** @test */
    public function generate_otp_throws_cooldown_exception_when_active_otp_exists_within_60_seconds(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000003';

        // Buat OTP pertama
        $this->service->generateOtp($user, $identifier, 'whatsapp');

        // Request kedua dalam cooldown → harus throw
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/^COOLDOWN:\d+$/');

        $this->service->generateOtp($user, $identifier, 'whatsapp');
    }

    /** @test */
    public function generate_otp_invalidates_old_otp_after_cooldown_elapsed(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000004';

        // Buat OTP lama yang dibuat 61 detik lalu (cooldown sudah lewat)
        OtpCode::create([
            'user_id'    => $user->id,
            'identifier' => $identifier,
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
            'attempts'   => 0,
            'created_at' => now()->subSeconds(61),
            'updated_at' => now()->subSeconds(61),
        ]);

        // Generate baru — tidak boleh throw
        $raw = $this->service->generateOtp($user, $identifier, 'whatsapp');
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $raw);

        // OTP lama harus di-mark is_used = 1
        $old = OtpCode::where('identifier', $identifier)
            ->where('code', hash('sha256', '123456'))
            ->first();
        $this->assertEquals(1, $old->is_used);
    }

    /** @test */
    public function generate_otp_sets_correct_expiry(): void
    {
        $user = User::factory()->create();

        $this->service->generateOtp($user, '087800000005', 'email');

        $record = OtpCode::where('identifier', '087800000005')->latest()->first();
        $this->assertNotNull($record);
        $this->assertTrue($record->expires_at->between(
            now()->addMinutes(4)->subSeconds(5),
            now()->addMinutes(5)->addSeconds(5)
        ));
    }

    // -------------------------------------------------------------------------
    // verifyOtp
    // -------------------------------------------------------------------------

    /** @test */
    public function verify_otp_returns_otp_record_on_valid_input(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000010';

        $raw = $this->service->generateOtp($user, $identifier, 'whatsapp');
        $result = $this->service->verifyOtp($identifier, $raw);

        $this->assertInstanceOf(OtpCode::class, $result);
        $this->assertEquals(1, $result->is_used);
    }

    /** @test */
    public function verify_otp_returns_false_when_no_active_otp(): void
    {
        $result = $this->service->verifyOtp('087800000011', '999999');
        $this->assertFalse($result);
    }

    /** @test */
    public function verify_otp_returns_false_on_wrong_code_and_increments_attempts(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000012';

        $this->service->generateOtp($user, $identifier, 'whatsapp');

        $result = $this->service->verifyOtp($identifier, '000000');
        $this->assertFalse($result);

        $record = OtpCode::where('identifier', $identifier)->latest()->first();
        $this->assertEquals(1, $record->attempts);
    }

    /** @test */
    public function verify_otp_invalidates_otp_after_max_attempts_reached(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000013';

        $this->service->generateOtp($user, $identifier, 'whatsapp');

        // 3x salah → max_attempts = 3
        $this->service->verifyOtp($identifier, '000001');
        $this->service->verifyOtp($identifier, '000002');
        $this->service->verifyOtp($identifier, '000003');

        $record = OtpCode::where('identifier', $identifier)->latest()->first();
        $this->assertEquals(1, $record->is_used);
    }

    /** @test */
    public function verify_otp_returns_false_for_expired_otp(): void
    {
        $user = User::factory()->create();
        $identifier = '087800000014';

        OtpCode::create([
            'user_id'    => $user->id,
            'identifier' => $identifier,
            'channel'    => 'whatsapp',
            'code'       => hash('sha256', '654321'),
            'expires_at' => now()->subMinute(), // sudah expired
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        $result = $this->service->verifyOtp($identifier, '654321');
        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // dispatchOtpNotification
    // -------------------------------------------------------------------------

    /** @test */
    public function dispatch_otp_notification_pushes_whatsapp_job_to_high_queue(): void
    {
        Queue::fake();

        $this->service->dispatchOtpNotification('123456', '087800000020', 'whatsapp');

        Queue::assertPushedOn('high', SendWhatsAppNotification::class);
        Queue::assertNotPushed(SendEmailNotification::class);
    }

    /** @test */
    public function dispatch_otp_notification_pushes_email_job_to_high_queue(): void
    {
        Queue::fake();

        $this->service->dispatchOtpNotification('123456', 'test@example.com', 'email');

        Queue::assertPushedOn('high', SendEmailNotification::class);
        Queue::assertNotPushed(SendWhatsAppNotification::class);
    }

    // -------------------------------------------------------------------------
    // maskDestination
    // -------------------------------------------------------------------------

    /** @test */
    public function mask_destination_masks_phone_number_correctly(): void
    {
        $masked = $this->service->maskDestination('087812345678');

        $this->assertStringStartsWith('08', $masked);
        $this->assertStringEndsWith('78', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    /** @test */
    public function mask_destination_masks_email_correctly(): void
    {
        $masked = $this->service->maskDestination('ahmad@gmail.com');

        $this->assertStringStartsWith('ah', $masked);
        $this->assertStringContainsString('@', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    /** @test */
    public function mask_destination_handles_short_string_without_panic(): void
    {
        $masked = $this->service->maskDestination('ab');
        $this->assertEquals('ab', $masked);
    }
}
