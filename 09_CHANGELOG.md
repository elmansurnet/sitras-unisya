# 09_CHANGELOG.md
# CHANGELOG — SITRAS UNISYA
# Format: Keep a Changelog (keepachangelog.com)
# Versi semantik: MAJOR.MINOR.PATCH

---

## [1.3.0] — 2026-06-09

### Added — Sesi 2A Batch B: Alumni Controllers, Policies, Jobs

**Repository Layer:**
- `app/Repositories/AlumniRepository.php` — paginate (search/filter/sort), findWithRelations, findByUserId, all (untuk export), stats (ringkasan per survey_status)

**Policy Layer:**
- `app/Policies/AlumniPolicy.php` — viewAny/view/create/update (superadmin+admin, alumni self-only), delete (superadmin only), import/export (superadmin+admin)
- `app/Providers/AuthServiceProvider.php` — register AlumniPolicy untuk Alumni::class

**Form Request Layer:**
- `app/Http/Requests/Alumni/StoreAlumniRequest.php` — validasi lengkap nim/nik/gpa/email unique, study_program/graduation_year exists
- `app/Http/Requests/Alumni/UpdateAlumniRequest.php` — validasi dengan Rule::unique()->ignore() untuk update
- `app/Http/Requests/Alumni/ImportAlumniRequest.php` — file mimes:xlsx,csv, max 10MB
- `app/Http/Requests/Alumni/SendInvitationRequest.php` — channel (whatsapp/email/both), questionnaire_id exists

**Controller Layer:**
- `app/Http/Controllers/Api/V1/Admin/AlumniController.php` — index (paginate+filter), show, store, update, destroy, import, export, importTemplate, stats, sendInvitation; response format sesuai 05_API.md §3.1–3.9
- `app/Http/Controllers/Api/V1/Alumni/ProfileController.php` — show, update (field terbatas untuk self), uploadPhoto; temporary URL untuk foto
- `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` — index/store/update/destroy (self), indexForAdmin; validasi is_current reset logic

**Job Layer:**
- `app/Jobs/SendBulkInvitationJob.php` — kirim undangan via WA Gateway UNISYA (HTTP POST ke wacenter.unisya.ac.id), update survey_status→terkirim, AuditLog, retry 3x, queue 'high'
- `app/Jobs/GenerateReportExport.php` — generate Excel via maatwebsite/excel, simpan ke storage/private/exports/, queue 'default'
- `app/Exports/AlumniExport.php` — Maatwebsite Excel export class dengan heading, auto-size, bold header

### Security
- AlumniPolicy delete hanya superadmin sesuai 07_SECURITY.md §3.3
- File upload validasi mimes + max size, disimpan ke storage/private
- Gate::authorize() digunakan di semua controller action (bukan hanya middleware)
- self-authorization check di WorkHistoryController dan ProfileController

---

## [1.2.0] — 2026-06-09

### Added — Sesi 2A Batch A: Alumni Migrations, Models, Services
- `database/migrations/*_create_alumni_table.php` — 30+ kolom, ENUM survey_status, index lengkap
- `database/migrations/*_create_alumni_work_histories_table.php` — ENUM employment_type, job_relevance
- `database/migrations/*_create_survey_responses_table.php` — skeleton untuk sesi 4A
- `app/Models/Alumni.php` — gpa cast decimal:2, SoftDeletes, relasi lengkap, isProfileComplete()
- `app/Models/AlumniWorkHistory.php` — relasi ke Alumni, SalaryRange, cast dates
- `app/Services/AlumniService.php` — create/update/delete (DB transaction), uploadPhoto (storage/private), import (batch), export (dispatch job), sendInvitation
- `app/Services/ImportExportService.php` — parseExcel, validateRows, generateTemplate
- `routes/api.php` — routes admin/alumni lengkap dengan urutan static routes before {param}

---

## [1.1.0] — 2026-06-09

### Added — Sesi 1B & 1C: Autentikasi Backend & Frontend
- `app/Services/OtpService.php` — generate (random_int + SHA-256), verify (hash_equals), cooldown Redis
- `app/Services/AuthService.php` — login OTP, login email/password, login employer token, logout
- `app/Http/Controllers/Api/V1/Auth/OtpController.php` — requestOtp, verifyOtp
- `app/Http/Controllers/Api/V1/Auth/AuthController.php` — login, logout, me, loginEmployer
- `app/Http/Middleware/EnsureAccountActive.php`
- `app/Http/Middleware/CheckRole.php`
- `app/Http/Middleware/LogActivity.php`
- `app/Http/Middleware/ValidateEmployerToken.php`
- Vue 3 frontend: authStore (Pinia), halaman Login, Axios interceptor, Route Guard

---

## [1.0.0] — 2026-06-09

### Added — Sesi 1A: Setup Proyek & Database
- Laravel 12 + Vue 3 + Vite 5 + TailwindCSS 3 project setup
- `config/tracer.php` — OTP, login, employer token config
- `config/cors.php` — FRONTEND_URL, supports_credentials: true, max_age: 86400
- Migrations: users, personal_access_tokens, otp_codes (VARCHAR(64) SHA-256), audit_logs, faculties, study_programs, graduation_years, system_settings, industry_sectors, salary_ranges
- Models: User, OtpCode, AuditLog, Faculty, StudyProgram, GraduationYear, SystemSetting, IndustrySector, SalaryRange
- Seeders: SuperadminSeeder, FacultySeeder, StudyProgramSeeder, GraduationYearSeeder, IndustrySectorSeeder, SalaryRangeSeeder, SystemSettingSeeder
- Observers placeholder: AlumniObserver, EmployerObserver, SurveyResponseObserver, UserObserver

### Security
- OTP stored as SHA-256 hex digest (VARCHAR(64)), bukan plaintext
- bcrypt cost factor 12 untuk password superadmin
- CORS: hanya FRONTEND_URL, credentials enabled
