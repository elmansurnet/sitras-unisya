<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $superadminUser;
    private User $alumniUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser     = User::factory()->create(['role' => 'admin']);
        $this->superadminUser = User::factory()->create(['role' => 'superadmin']);
        $this->alumniUser    = User::factory()->create(['role' => 'alumni']);
    }

    // ─────────────────────────────────────────
    // GET /api/v1/admin/notifications/templates
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_list_notification_templates(): void
    {
        NotificationTemplate::factory(3)->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/templates');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'event', 'is_active'],
                ],
            ]);
    }

    /** @test */
    public function list_can_filter_templates_by_type(): void
    {
        NotificationTemplate::factory(2)->create(['type' => 'email']);
        NotificationTemplate::factory(2)->create(['type' => 'whatsapp']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/templates?type=email');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function unauthenticated_user_cannot_list_templates(): void
    {
        $this->getJson('/api/v1/admin/notifications/templates')
            ->assertUnauthorized();
    }

    /** @test */
    public function alumni_cannot_access_notification_templates(): void
    {
        $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/templates')
            ->assertForbidden();
    }

    // ─────────────────────────────────────────
    // GET /api/v1/admin/notifications/templates/{id}
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_show_a_single_template(): void
    {
        $template = NotificationTemplate::factory()->create();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/admin/notifications/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonStructure(['data' => ['id', 'name', 'type', 'event', 'subject', 'body', 'variables', 'is_active']]);
    }

    /** @test */
    public function show_nonexistent_template_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/admin/notifications/templates/9999')
            ->assertNotFound();
    }

    // ─────────────────────────────────────────
    // POST /api/v1/admin/notifications/templates
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_create_a_notification_template(): void
    {
        $payload = [
            'name'      => 'Template Undangan Baru',
            'type'      => 'whatsapp',
            'event'     => 'survey_invitation_custom',
            'body'      => 'Halo {nama_alumni}, silakan isi survei di {link_survei}.',
            'variables' => [
                ['key' => 'nama_alumni', 'description' => 'Nama alumni'],
                ['key' => 'link_survei', 'description' => 'Link survei'],
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/notifications/templates', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Template Undangan Baru');

        $this->assertDatabaseHas('notification_templates', [
            'name'  => 'Template Undangan Baru',
            'type'  => 'whatsapp',
            'event' => 'survey_invitation_custom',
        ]);
    }

    /** @test */
    public function create_template_fails_with_duplicate_type_event(): void
    {
        NotificationTemplate::factory()->create([
            'type'  => 'email',
            'event' => 'survey_invitation',
        ]);

        $payload = [
            'name'  => 'Duplikat',
            'type'  => 'email',
            'event' => 'survey_invitation', // sudah ada
            'body'  => 'Isi duplikat',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/notifications/templates', $payload)
            ->assertUnprocessable();
    }

    /** @test */
    public function create_template_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/notifications/templates', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type', 'event', 'body']);
    }

    /** @test */
    public function create_template_validates_type_enum(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/notifications/templates', [
                'name'  => 'Invalid',
                'type'  => 'telegram',  // bukan enum valid
                'event' => 'test',
                'body'  => 'Test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    // ─────────────────────────────────────────
    // PUT /api/v1/admin/notifications/templates/{id}
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_update_a_notification_template(): void
    {
        $template = NotificationTemplate::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/admin/notifications/templates/{$template->id}", [
                'name'      => 'Template Diperbarui',
                'type'      => $template->type,
                'event'     => $template->event,
                'body'      => 'Body baru {variabel}',
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Template Diperbarui');

        $this->assertDatabaseHas('notification_templates', [
            'id'        => $template->id,
            'name'      => 'Template Diperbarui',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function update_nonexistent_template_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson('/api/v1/admin/notifications/templates/9999', [
                'name' => 'Ghost', 'type' => 'email', 'event' => 'test', 'body' => 'x',
            ])
            ->assertNotFound();
    }

    // ─────────────────────────────────────────
    // DELETE /api/v1/admin/notifications/templates/{id}
    // ─────────────────────────────────────────

    /** @test */
    public function admin_can_delete_a_notification_template(): void
    {
        $template = NotificationTemplate::factory()->create();

        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/admin/notifications/templates/{$template->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notification_templates', ['id' => $template->id]);
    }

    /** @test */
    public function delete_nonexistent_template_returns_404(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson('/api/v1/admin/notifications/templates/9999')
            ->assertNotFound();
    }
}
