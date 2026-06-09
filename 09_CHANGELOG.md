# 09_CHANGELOG.md
# CHANGELOG — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.6 | Tanggal: 2026-06-09

---

## KONVENSI CHANGELOG

Setiap entri changelog mengikuti format:

```
## [Versi] — YYYY-MM-DD
### Kategori Perubahan
- Deskripsi perubahan spesifik [File Terdampak]
```

**Kategori:**
- `Added` — Fitur / konten baru
- `Fixed` — Perbaikan inkonsistensi / bug dokumentasi
- `Changed` — Perubahan yang tidak breaking
- `Removed` — Konten yang dihapus
- `Security` — Perbaikan keamanan
- `Deprecated` — Fitur yang akan dihapus di versi mendatang

---

## [1.0.6] — 2026-06-09

> **Sumber:** Sesi 2A Batch 1 — Migration + Model + Observer Alumni.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Perubahan berisi penambahan file kode produksi — bukan perubahan dokumentasi spesifikasi.**

---

### Added — File Kode Produksi Sesi 2A Batch 1

#### Added — Migrations (2 file)
- `database/migrations/2026_06_09_000010_create_alumni_table.php`
  — Tabel `alumni`: 25 kolom; `gpa DECIMAL(4,2)`; `survey_status ENUM(4 nilai)`;
    `latitude/longitude DECIMAL(10,7)`; `foto_path VARCHAR(255)` (private storage);
    SoftDeletes; 9 index sesuai 02_DATABASE.md §2.3
- `database/migrations/2026_06_09_000011_create_alumni_work_histories_table.php`
  — Tabel `alumni_work_histories`: FK ke `alumni` (cascade delete),
    FK ke `employers` (nullable, null on delete), FK ke `salary_ranges` (nullable);
    `is_current BOOLEAN`; `source ENUM(alumni/admin/survey)`; 3 index

#### Added — Models (2 file)
- `app/Models/Alumni.php`
  — `$fillable` (25 kolom), SoftDeletes, `$casts`: `gpa→'decimal:2'` (**KRITIS**: number bukan string),
    `latitude/longitude→float`, date/datetime casts;
    Relationships: `user()` (withTrashed), `studyProgram()`, `graduationYear()`, `workHistories()`,
    `currentJob()`, `employers()`, `surveyResponses()`;
    Scopes: `withSurveyStatus`, `notYetSurveyed`, `pendingSurvey`, `byStudyProgram`, `byGraduationYear`;
    Helpers: `hasSurveyCompleted()`, `isProfileComplete()`, `profileCompletionPercentage()`
- `app/Models/AlumniWorkHistory.php`
  — `$fillable` (12 kolom), `$casts` (dates, boolean);
    Relationships: `alumni()`, `employer()` (nullable), `salaryRange()`;
    Scopes: `current()`, `past()`, `bySource()`;
    Accessor: `getDurationAttribute()` (durasi pekerjaan human-readable)

#### Added — Observer (1 file)
- `app/Observers/AlumniObserver.php`
  — Mengisi placeholder observer dari Sesi 1A;
    Method: `created`, `updated` (only dirty columns), `deleted`, `restored`, `forceDeleted`;
    Setiap event → `AuditLog::record()` sesuai 07_SECURITY.md §8.2 & §8.3;
    `updated()` hanya mencatat kolom yang benar-benar berubah (skip `updated_at` murni)

---

### Notes — Keputusan Implementasi

- **`gpa` cast `'decimal:2'`**: Eloquent default return DECIMAL sebagai string. Cast ini memastikan
  gpa selalu menjadi float (number) di JSON response — sesuai fix INC-04 v1.0.1 dan aturan SITRAS.
- **`Alumni::employers()` dideklarasikan sekarang**: Model Employer belum ada (dibuat Sesi 2B).
  Relasi BelongsToMany ini tidak menyebabkan error karena eager loading tidak otomatis.
  Tabel pivot `alumni_employer` dibuat di Sesi 2B.
- **`Alumni::surveyResponses()` dideklarasikan sekarang**: Model SurveyResponse dibuat Sesi 4A.
  Sama seperti employers() — tidak error karena tidak auto-eager-load.
- **`AlumniWorkHistory` FK `employer_id` nullable**: Migration sudah mencantumkan FK ke `employers`
  meski tabel `employers` belum ada. **Perlu dipastikan**: migration ini dijalankan SETELAH
  migration employers di Sesi 2B. Untuk development sequential, `php artisan migrate` dijalankan
  setelah Sesi 2B selesai. Jika perlu jalankan sekarang, buat migration tanpa FK dulu dan tambahkan
  FK via migration alter di Sesi 2B.

---

### Ringkasan File Terdampak v1.0.6

| File | Aksi | Keterangan |
|---|---|---|
| `database/migrations/2026_06_09_000010_create_alumni_table.php` | Added | Tabel alumni lengkap sesuai 02_DATABASE.md §2.3 |
| `database/migrations/2026_06_09_000011_create_alumni_work_histories_table.php` | Added | Tabel riwayat kerja alumni |
| `app/Models/Alumni.php` | Added | Model alumni production-ready |
| `app/Models/AlumniWorkHistory.php` | Added | Model riwayat kerja |
| `app/Observers/AlumniObserver.php` | Added | Observer audit trail (mengisi placeholder 1A) |
| `08_PHASE_TRACKER.md` | Changed | 2A.1–2A.4 → ✅; counter 47→51; Fase 2 → 🔄 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 5 file kode ditambah, 2 dokumen diupdate**
**Sesi 2A progress: 4/31 task ✅**
**Task selesai keseluruhan: 51/199**

---

## [1.0.5] — 2026-06-09

> **Sumber:** Penyelesaian Sesi 1B — Sistem Autentikasi Backend + Frontend.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).

### Added — File Kode Produksi Sesi 1B

#### Added — Middleware (4 file)
- `app/Http/Middleware/CheckRole.php`
- `app/Http/Middleware/EnsureAccountActive.php`
- `app/Http/Middleware/ValidateEmployerToken.php`
- `app/Http/Middleware/LogActivity.php`

#### Added — Services (2 file)
- `app/Services/OtpService.php`
- `app/Services/AuthService.php`

#### Added — Controllers (3 file)
- `app/Http/Controllers/Api/V1/Auth/OtpController.php`
- `app/Http/Controllers/Api/V1/Auth/AuthController.php`
- `app/Http/Controllers/Api/V1/Public/PublicController.php`

#### Added — Form Requests (3 file)
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/OtpRequestRequest.php`
- `app/Http/Requests/Auth/OtpVerifyRequest.php`

#### Added — Jobs (2 file)
- `app/Jobs/SendWhatsAppNotification.php`
- `app/Jobs/SendEmailNotification.php`

#### Added — Routes
- `routes/api.php` — `/api/v1/auth/*` + `/api/v1/public/*`

#### Added — Frontend (11 file)
- `resources/js/services/api.js`
- `resources/js/stores/auth.js`
- `resources/js/layouts/AuthLayout.vue`
- `resources/js/pages/auth/LoginPage.vue`
- `resources/js/pages/auth/OtpRequestPage.vue`
- `resources/js/pages/auth/OtpVerifyPage.vue`
- `resources/js/pages/auth/EmployerTokenPage.vue`
- `resources/js/router/index.js`
- `resources/js/layouts/AdminLayout.vue`
- `resources/js/layouts/AlumniLayout.vue`
- `resources/js/layouts/EmployerLayout.vue`

#### Added — Tests (3 file)
- `tests/Feature/Auth/AdminLoginTest.php`
- `tests/Feature/Auth/OtpTest.php`
- `tests/Feature/Auth/EmployerTokenTest.php`

#### Changed
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`

**Total: ~35 file | 1B complete: 28/28 ✅ | Task selesai: 47/199**

---

## [1.0.4] — 2026-06-09

> **Sumber:** Penyelesaian Sesi 1A — Setup Proyek & Database.

### Added — Migrations (10 file)
- `users`, `personal_access_tokens`, `otp_codes` (VARCHAR(64)), `audit_logs`,
  `faculties`, `study_programs`, `graduation_years`, `system_settings`,
  `industry_sectors`, `salary_ranges`

### Added — Models (9 file)
- `User`, `OtpCode`, `AuditLog`, `Faculty`, `StudyProgram`, `GraduationYear`,
  `SystemSetting`, `IndustrySector`, `SalaryRange`

### Added — Seeders (8 file)
- `SuperadminSeeder`, `FacultySeeder`, `StudyProgramSeeder`, `GraduationYearSeeder`,
  `IndustrySectorSeeder`, `SalaryRangeSeeder`, `SystemSettingSeeder`, `DatabaseSeeder`

### Added — Config (3 file)
- `config/tracer.php`, `config/whatsapp.php`, `config/cors.php`

### Added — Observers (4 file placeholder)
- `AlumniObserver`, `EmployerObserver`, `SurveyResponseObserver`, `UserObserver`

### Changed
- `config/database.php`, `config/queue.php`, `config/session.php`
- `vite.config.js`, `tailwind.config.js`, `package.json`, `.env.example`
- `app/Providers/AppServiceProvider.php`

**Total: 37 file | 1A complete: 19/19 ✅ | Task selesai: 19/199**

---

## [1.0.3] — 2026-06-09

> **Sumber:** Audit konsistensi dokumen v1.0.3 sebelum development dimulai.

### Fixed
- [INC-01] `01_BLUEPRINT.md`: tabel identitas proyek versi & tanggal tidak sinkron
- [INC-02] `04_ARCHITECTURE.md`: diagram External Services masih label `(Fonnte/Wablas)`
- [INC-03] `08_PHASE_TRACKER.md`: header "Total Task: 167" → 199
- [INC-04] `05_API.md`: catatan routing reorder Laravel ditambahkan
- [INC-05] `07_SECURITY.md`: matriks izin profil alumni diperjelas
- [INC-06/07] `04_ARCHITECTURE.md`: folder structure pages dilengkapi nama file .vue

**6 file direvisi | 0 perubahan skema | 0 perubahan API endpoint**

---

## [1.0.2] — 2026-06-08

> **Sumber:** Audit kesesuaian WA Gateway UNISYA (`wacenter.unisya.ac.id`).

### Changed
- Seluruh dokumen: WA Gateway dari Fonnte/Wablas → `wacenter.unisya.ac.id`
- `05_API.md`: group settings `whatsapp` → 3 key (`wa_gateway_url`, `wa_api_key`, `wa_sender`)
- `07_SECURITY.md`: SSRF whitelist diperbarui
- `08_PHASE_TRACKER.md`: task 4A.11 & 1A.17 diperinci

### Added
- `02_DATABASE.md`, `03_ERD.md`: catatan status `delivered` tidak auto-diisi dari gateway UNISYA

**9 file direvisi | 0 perubahan skema | 0 perubahan endpoint**

---

## [1.0.1] — 2026-06-06

> **Sumber:** Audit konsistensi lintas-dokumen (01–09).

### Fixed (Critical)
- `otp_codes.code` VARCHAR(10) → VARCHAR(64) untuk SHA-256 hash

### Fixed (Major)
- Actor `admin` tidak terdefinisi di Blueprint
- Endpoint CRUD notification templates & log hilang dari API spec

### Fixed (Moderate)
- Tipe `gpa` string → number di API response examples
- Route `/alumni/work-history` → `/alumni/work-histories`
- Summary table API tidak lengkap (73 endpoint)

### Added
- Section 9 API: 6 endpoint manajemen notifikasi
- 7 endpoint yang hilang
- Catatan desain relasi `survey_periods ↔ questionnaires`

**9 file direvisi | 22 inkonsistensi ditemukan & diperbaiki**

---

## [1.0.0] — 2026-06-04

> Dokumen awal sistem SITRAS UNISYA.

### Added
- `01_BLUEPRINT.md` hingga `09_CHANGELOG.md` — semua dokumen spesifikasi awal

---

## CATATAN INKONSISTENSI YANG DITEMUKAN & STATUS

| # | Tingkat | Deskripsi | Status |
|---|---|---|---|
| 1 | 🔴 Critical | `otp_codes.code` VARCHAR(10) → VARCHAR(64) untuk SHA-256 | ✅ Fixed v1.0.1 |
| 2 | 🟠 Major | Actor `admin` tidak terdefinisi di Blueprint | ✅ Fixed v1.0.1 |
| 3 | 🟠 Major | Endpoint CRUD notification templates & log hilang dari API spec | ✅ Fixed v1.0.1 |
| 4 | 🟡 Moderate | Tipe `gpa` string vs number di API response | ✅ Fixed v1.0.1 |
| 5 | 🟡 Moderate | Route `/alumni/work-history` ≠ API `/alumni/work-histories` | ✅ Fixed v1.0.1 |
| 6 | 🟡 Moderate | Summary table API tidak lengkap | ✅ Fixed v1.0.1 |
| 7 | 🟡 Moderate | Beberapa endpoint ada di Architecture/UI/UX tapi tidak di API spec | ✅ Fixed v1.0.1 |
| 8 | 🟢 Minor | CSP header berbeda antara Architecture dan Security doc | ✅ Fixed v1.0.1 |
| 9 | 🟢 Minor | Relasi survey_periods ↔ questionnaires tidak terdokumentasi | ✅ Fixed v1.0.1 |
| 10 | 🟢 Minor | Status survei alumni di Blueprint berbeda dari ENUM database | ✅ Fixed v1.0.1 |
| 11 | 🟢 Minor | Alur login admin tidak terdokumentasi di Blueprint | ✅ Fixed v1.0.1 |
| 12 | 🟢 Minor | DELETE employer hilang dari matriks izin Security | ✅ Fixed v1.0.1 |
| 13 | 🟢 Minor | Beberapa komponen frontend tidak ada di UI/UX spec | ✅ Fixed v1.0.1 |
| 14 | 🟢 Minor | Claim "tidak ada konflik" di Changelog v1.0.0 tidak akurat | ✅ Fixed v1.0.1 |
| 15 | 🟠 Major | WA Gateway masih Fonnte/Wablas; seharusnya wacenter.unisya.ac.id | ✅ Fixed v1.0.2 |
| 16 | 🟡 Moderate | `notification_logs.status delivered` tidak bisa diisi otomatis | ✅ Fixed v1.0.2 |
| 17 | 🟠 Major | [INC-01] Blueprint: tabel identitas tidak sinkron dengan header | ✅ Fixed v1.0.3 |
| 18 | 🟠 Major | [INC-02] Architecture: diagram External Services label Fonnte/Wablas | ✅ Fixed v1.0.3 |
| 19 | 🟡 Moderate | [INC-03] Phase Tracker: header "Total Task: 167" | ✅ Fixed v1.0.3 |
| 20 | 🟡 Moderate | [INC-04] API: endpoint reorder tidak ada catatan routing Laravel | ✅ Fixed v1.0.3 |
| 21 | 🟡 Moderate | [INC-05] Security: matriks izin profil alumni ambigu | ✅ Fixed v1.0.3 |
| 22 | 🟢 Minor | [INC-06/07] Architecture: folder structure pages tidak lengkap | ✅ Fixed v1.0.3 |

**Total: 22 inkonsistensi ditemukan sejak v1.0.0 — semua telah diperbaiki**
**Status: ✅ Dokumen SITRAS UNISYA v1.0.6 CLEAR | Development Progress: 51/199 task**

---

## DOKUMEN TERDAMPAK PER FILE

| File | Versi | Jenis |
|---|---|---|
| 01_BLUEPRINT.md | 1.0.3 | Fixed |
| 02_DATABASE.md | 1.0.2 | Fixed+Added |
| 03_ERD.md | 1.0.2 | Fixed+Added |
| 04_ARCHITECTURE.md | 1.0.3 | Fixed+Added |
| 05_API.md | 1.0.3 | Added+Fixed |
| 06_UI_UX.md | 1.0.2 | Fixed+Added |
| 07_SECURITY.md | 1.0.3 | Fixed+Changed |
| 08_PHASE_TRACKER.md | 1.0.6 | Changed |
| 09_CHANGELOG.md | 1.0.6 | Added |

---

## RIWAYAT VERSI DOKUMEN INI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Audit konsistensi — 14 inkonsistensi; tabel ringkasan; file terdampak |
| 1.0.2 | 2026-06-08 | Audit WA Gateway UNISYA — 9 file direvisi |
| 1.0.3 | 2026-06-09 | Audit v1.0.3 — 8 inkonsistensi (6 file direvisi) |
| 1.0.4 | 2026-06-09 | Sesi 1A — 37 file produksi; 19/199 task |
| 1.0.5 | 2026-06-09 | Sesi 1B — ~35 file produksi; 47/199 task |
| 1.0.6 | 2026-06-09 | Sesi 2A Batch 1 — 5 file kode; 51/199 task |

---

*Dokumen ini adalah catatan resmi semua perubahan pada dokumentasi proyek SITRAS UNISYA.*
