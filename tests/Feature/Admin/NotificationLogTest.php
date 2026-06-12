<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $superadminUser;
    private User $alumniUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser      = User::factory()->create(['role' => 'admin']);
        $this->superadminUser = User::factory()->create(['role' => 'superadmin']);
        $this->alumniUser     = User::factory()->create(['role' => 'alumni']);
    }

    // ─────────────────────────────────────────
    // GET /api/v1/admin/notifications/logs
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_list_notification_logs(): void
    {
        NotificationLog::factory(5)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'type', 'recipient', 'status', 'sent_at', 'created_at'],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function logs_are_paginated(): void
    {
        NotificationLog::factory(25)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?per_page=10&page=1');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertNotNull($response->json('meta.total'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_logs(): void
    {
        $this->getJson('/api/v1/admin/notifications/logs')
            ->assertUnauthorized();
    }

    /** @test */
    public function alumni_cannot_access_notification_logs(): void
    {
        $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs')
            ->assertForbidden();
    }

    // ─────────────────────────────────────────
    // Filter: type
    // ─────────────────────────────────────────

    /** @test */
    public function logs_can_be_filtered_by_type_email(): void
    {
        NotificationLog::factory(3)->create(['type' => 'email']);
        NotificationLog::factory(2)->create(['type' => 'whatsapp']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?type=email');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
        foreach ($data as $log) {
            $this->assertEquals('email', $log['type']);
        }
    }

    /** @test */
    public function logs_can_be_filtered_by_type_whatsapp(): void
    {
        NotificationLog::factory(4)->create(['type' => 'whatsapp']);
        NotificationLog::factory(2)->create(['type' => 'email']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?type=whatsapp');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(4, $data);
    }

    // ─────────────────────────────────────────
    // Filter: status
    // ─────────────────────────────────────────

    /** @test */
    public function logs_can_be_filtered_by_status_sent(): void
    {
        NotificationLog::factory(3)->create(['status' => 'sent']);
        NotificationLog::factory(2)->create(['status' => 'failed']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?status=sent');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function logs_can_be_filtered_by_status_failed(): void
    {
        NotificationLog::factory(2)->create(['status' => 'failed']);
        NotificationLog::factory(4)->create(['status' => 'sent']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?status=failed');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    // ─────────────────────────────────────────
    // Filter: recipient_type
    // ─────────────────────────────────────────

    /** @test */
    public function logs_can_be_filtered_by_recipient_type(): void
    {
        NotificationLog::factory(3)->create(['recipient_type' => 'alumni']);
        NotificationLog::factory(2)->create(['recipient_type' => 'employer']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?recipient_type=alumni');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // ─────────────────────────────────────────
    // Filter: date range (date_from & date_to)
    // ─────────────────────────────────────────

    /** @test */
    public function logs_can_be_filtered_by_date_from(): void
    {
        NotificationLog::factory(2)->create(['created_at' => now()->subDays(10)]);
        NotificationLog::factory(3)->create(['created_at' => now()->subDay()]);

        $dateFrom = now()->subDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/admin/notifications/logs?date_from={$dateFrom}");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function logs_can_be_filtered_by_date_to(): void
    {
        NotificationLog::factory(3)->create(['created_at' => now()->subDays(10)]);
        NotificationLog::factory(2)->create(['created_at' => now()]);

        $dateTo = now()->subDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/admin/notifications/logs?date_to={$dateTo}");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function logs_can_be_filtered_by_date_range(): void
    {
        NotificationLog::factory(2)->create(['created_at' => now()->subDays(20)]);
        NotificationLog::factory(4)->create(['created_at' => now()->subDays(5)]);
        NotificationLog::factory(1)->create(['created_at' => now()]);

        $dateFrom = now()->subDays(7)->format('Y-m-d');
        $dateTo   = now()->subDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/admin/notifications/logs?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertOk();
        $this->assertCount(4, $response->json('data'));
    }

    // ─────────────────────────────────────────
    // Multiple filters combined
    // ─────────────────────────────────────────

    /** @test */
    public function logs_can_combine_multiple_filters(): void
    {
        NotificationLog::factory(2)->create([
            'type'   => 'whatsapp',
            'status' => 'sent',
        ]);
        NotificationLog::factory(3)->create([
            'type'   => 'email',
            'status' => 'sent',
        ]);
        NotificationLog::factory(2)->create([
            'type'   => 'whatsapp',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?type=whatsapp&status=sent');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function log_filter_with_invalid_type_returns_422(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?type=telegram')
            ->assertUnprocessable();
    }

    /** @test */
    public function log_filter_with_invalid_status_returns_422(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs?status=unknown')
            ->assertUnprocessable();
    }

    /** @test */
    public function superadmin_can_also_access_logs(): void
    {
        NotificationLog::factory(2)->create();

        $this->actingAs($this->superadminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/logs')
            ->assertOk();
    }
}
