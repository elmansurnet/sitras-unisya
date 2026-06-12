<?php

namespace Tests\Feature\Admin;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendEmailNotification;
use Tests\TestCase;

class EmployerTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $alumniUser;
    private User $employerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin   = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->admin        = User::factory()->create(['role' => 'admin',      'is_active' => true]);
        $this->alumniUser   = User::factory()->create(['role' => 'alumni',     'is_active' => true]);
        $this->employerUser = User::factory()->create(['role' => 'employer',   'is_active' => true]);
    }

    // ─────────────────────────────────────────────
    // SEND SURVEY TOKEN
    // ─────────────────────────────────────────────

    public function test_admin_can_send_survey_token_via_whatsapp(): void
    {
        Queue::fake();

        $employer = Employer::factory()->create([
            'survey_status' => 'belum_disurvei',
            'survey_token'  => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'whatsapp',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('employers', [
            'id'            => $employer->id,
            'survey_status' => 'terkirim',
        ]);

        // Token harus ter-generate
        $employer->refresh();
        $this->assertNotNull($employer->survey_token);
        $this->assertEquals(64, strlen($employer->survey_token));

        // Token expiry = 30 hari ke depan
        $this->assertNotNull($employer->survey_token_expires_at);
        $this->assertTrue($employer->survey_token_expires_at->isFuture());

        // Job harus dispatch ke queue
        Queue::assertPushedOn('notifications', SendWhatsAppNotification::class);
    }

    public function test_admin_can_send_survey_token_via_email(): void
    {
        Queue::fake();

        $employer = Employer::factory()->create([
            'survey_status' => 'belum_disurvei',
            'survey_token'  => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'email',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        Queue::assertPushedOn('notifications', SendEmailNotification::class);
    }

    public function test_send_survey_token_validates_channel(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'belum_disurvei']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'telegram',  // channel tidak valid
            ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'errors' => ['channel']]);
    }

    public function test_send_survey_token_validates_channel_required(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'belum_disurvei']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'errors' => ['channel']]);
    }

    public function test_cannot_send_survey_token_to_employer_who_completed_survey(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'selesai']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'whatsapp',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_send_survey_token_creates_audit_log(): void
    {
        Queue::fake();

        $employer = Employer::factory()->create(['survey_status' => 'belum_disurvei']);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'whatsapp',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'send_survey_token',
            'module' => 'Employer',
        ]);
    }

    public function test_alumni_cannot_send_survey_token(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'belum_disurvei']);

        $response = $this->actingAs($this->alumniUser)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'whatsapp',
            ]);

        $response->assertForbidden();
    }

    public function test_employer_role_cannot_send_survey_token(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'belum_disurvei']);

        $response = $this->actingAs($this->employerUser)
            ->postJson("/api/v1/admin/employers/{$employer->id}/send-survey-token", [
                'channel' => 'whatsapp',
            ]);

        $response->assertForbidden();
    }

    public function test_send_survey_token_returns_404_for_nonexistent_employer(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers/999999/send-survey-token', [
                'channel' => 'whatsapp',
            ]);

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────
    // REGENERATE TOKEN
    // ─────────────────────────────────────────────

    public function test_admin_can_regenerate_survey_token(): void
    {
        $oldToken = str_repeat('a', 64);

        $employer = Employer::factory()->create([
            'survey_status'           => 'terkirim',
            'survey_token'            => $oldToken,
            'survey_token_expires_at' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $employer->refresh();

        // Token harus berubah
        $this->assertNotEquals($oldToken, $employer->survey_token);
        $this->assertEquals(64, strlen($employer->survey_token));

        // Status tetap terkirim
        $this->assertEquals('terkirim', $employer->survey_status);

        // Expires at harus diperbarui
        $this->assertTrue($employer->survey_token_expires_at->isAfter(now()->addDays(29)));
    }

    public function test_superadmin_can_regenerate_survey_token(): void
    {
        $employer = Employer::factory()->create([
            'survey_status' => 'terkirim',
            'survey_token'  => str_repeat('b', 64),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_cannot_regenerate_token_for_completed_employer(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'selesai']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_regenerate_token_creates_audit_log_with_warning_level(): void
    {
        $employer = Employer::factory()->create([
            'survey_status' => 'terkirim',
            'survey_token'  => str_repeat('c', 64),
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'regenerate_token',
            'module' => 'Employer',
            'level'  => 'warning',
        ]);
    }

    public function test_alumni_cannot_regenerate_token(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'terkirim']);

        $response = $this->actingAs($this->alumniUser)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $response->assertForbidden();
    }

    public function test_employer_role_cannot_regenerate_token(): void
    {
        $employer = Employer::factory()->create(['survey_status' => 'terkirim']);

        $response = $this->actingAs($this->employerUser)
            ->postJson("/api/v1/admin/employers/{$employer->id}/regenerate-token");

        $response->assertForbidden();
    }

    public function test_regenerate_token_returns_404_for_nonexistent_employer(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers/999999/regenerate-token');

        $response->assertNotFound();
    }

    public function test_regenerated_token_is_unique(): void
    {
        $employer1 = Employer::factory()->create([
            'survey_status' => 'terkirim',
            'survey_token'  => str_repeat('x', 64),
        ]);
        $employer2 = Employer::factory()->create([
            'survey_status' => 'terkirim',
            'survey_token'  => str_repeat('y', 64),
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer1->id}/regenerate-token");

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/employers/{$employer2->id}/regenerate-token");

        $employer1->refresh();
        $employer2->refresh();

        $this->assertNotEquals($employer1->survey_token, $employer2->survey_token);
    }

    // ─────────────────────────────────────────────
    // EMPLOYER PROFILE ACCESS
    // ─────────────────────────────────────────────

    public function test_employer_can_view_own_profile(): void
    {
        Employer::factory()->create(['user_id' => $this->employerUser->id]);

        $response = $this->actingAs($this->employerUser)
            ->getJson('/api/v1/employer/profile');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_alumni_cannot_access_employer_profile_endpoint(): void
    {
        $response = $this->actingAs($this->alumniUser)
            ->getJson('/api/v1/employer/profile');

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_employer_profile_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/employer/profile');

        $response->assertForbidden();
    }
}
