<?php

namespace Tests\Feature\Alumni;

use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature Test: Alumni Profile API
 *
 * Endpoint yang diuji:
 *   GET  /api/v1/alumni/profile          — 05_API.md §11.1
 *   PUT  /api/v1/alumni/profile          — 05_API.md §11.2
 *   POST /api/v1/alumni/profile/photo    — 05_API.md §11.3
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;
    private Alumni $alumni;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        // Buat struktur data minimum yang dibutuhkan
        $faculty       = Faculty::factory()->create();
        $studyProgram  = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $graduationYear = GraduationYear::factory()->create(['year' => 2022]);

        $this->alumniUser = User::factory()->create([
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $this->alumni = Alumni::factory()->create([
            'user_id'            => $this->alumniUser->id,
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $graduationYear->id,
            'nim'                => '20210001',
            'full_name'          => 'Budi Santoso',
            'is_active'          => true,
        ]);

        $this->token = $this->alumniUser->createToken('test')->plainTextToken;
    }

    // =========================================================================
    // GET /api/v1/alumni/profile
    // =========================================================================

    /** @test */
    public function alumni_can_get_own_profile(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/alumni/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'user_id', 'nim', 'full_name',
                    'study_program_id', 'graduation_year_id',
                    'gpa', 'is_active',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nim', '20210001')
            ->assertJsonPath('data.full_name', 'Budi Santoso');
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile(): void
    {
        $this->getJson('/api/v1/alumni/profile')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_alumni_role_cannot_access_alumni_profile_endpoint(): void
    {
        $adminUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $token     = $adminUser->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/alumni/profile')
            ->assertStatus(404); // alumni record tidak ada untuk admin
    }

    // =========================================================================
    // PUT /api/v1/alumni/profile
    // =========================================================================

    /** @test */
    public function alumni_can_update_own_profile(): void
    {
        $payload = [
            'full_name'    => 'Budi Santoso Updated',
            'phone'        => '081234567890',
            'address_city' => 'Surabaya',
            'linkedin_url' => 'https://linkedin.com/in/budi-santoso',
        ];

        $response = $this->withToken($this->token)
            ->putJson('/api/v1/alumni/profile', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Budi Santoso Updated')
            ->assertJsonPath('data.address_city', 'Surabaya');

        $this->assertDatabaseHas('alumni', [
            'id'           => $this->alumni->id,
            'full_name'    => 'Budi Santoso Updated',
            'address_city' => 'Surabaya',
        ]);
    }

    /** @test */
    public function alumni_cannot_update_protected_fields(): void
    {
        // nim, user_id, study_program_id, is_active tidak boleh bisa diubah
        // oleh alumni sendiri (hanya admin yang bisa)
        $originalNim = $this->alumni->nim;

        $this->withToken($this->token)
            ->putJson('/api/v1/alumni/profile', [
                'nim'       => '99999999',
                'is_active' => false,
            ]);

        // nim tidak berubah di DB
        $this->assertDatabaseHas('alumni', [
            'id'  => $this->alumni->id,
            'nim' => $originalNim,
        ]);
    }

    /** @test */
    public function profile_update_validates_linkedin_url_format(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/alumni/profile', [
                'linkedin_url' => 'bukan-url-valid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['linkedin_url']);
    }

    /** @test */
    public function profile_update_validates_phone_format(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/alumni/profile', [
                'phone' => 'abc-bukan-nomor',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    // =========================================================================
    // POST /api/v1/alumni/profile/photo
    // =========================================================================

    /** @test */
    public function alumni_can_upload_profile_photo(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 400, 400)->size(500);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/alumni/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['photo_url']]);

        // File tersimpan di storage private (bukan public)
        $this->alumni->refresh();
        $this->assertNotNull($this->alumni->photo_path);
        Storage::disk('private')->assertExists($this->alumni->photo_path);
    }

    /** @test */
    public function photo_upload_rejects_non_image_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $this->withToken($this->token)
            ->postJson('/api/v1/alumni/profile/photo', [
                'photo' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }

    /** @test */
    public function photo_upload_rejects_file_over_size_limit(): void
    {
        // Limit: 2048 KB (2 MB) sesuai ProfileController
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);

        $this->withToken($this->token)
            ->postJson('/api/v1/alumni/profile/photo', [
                'photo' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);
    }
}
