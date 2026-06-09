# 09 CHANGELOG — SITRAS UNISYA

Semua perubahan signifikan pada codebase dan dokumen dicatat di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

---

## [1.2.0] — 2026-06-09 (Sesi 1B — Auth Backend + Frontend Core)

### Added

**Backend**
- `app/Http/Middleware/CheckRole.php` — RBAC middleware, validasi role dari Sanctum token
- `app/Http/Middleware/EnsureAccountActive.php` — cek `is_active=1` dan bukan terkunci
- `app/Http/Middleware/ValidateEmployerToken.php` — validasi employer survey token (64 char, belum expired, belum dipakai)
- `app/Http/Middleware/LogActivity.php` — catat setiap request ke `audit_logs` via AuditLog::record()
- `app/Http/Middleware/SecurityHeaders.php` — inject CSP, X-Frame-Options, HSTS, Referrer-Policy
- `app/Http/Controllers/Api/V1/Auth/AuthController.php` — loginAdmin, logout, me
- `app/Http/Controllers/Api/V1/Auth/OtpController.php` — requestOtp, verifyOtp dengan SHA-256
- `app/Http/Controllers/Api/V1/Auth/EmployerAuthController.php` — tokenAccess
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/OtpRequestRequest.php`
- `app/Http/Requests/Auth/OtpVerifyRequest.php`
- `app/Jobs/SendWhatsAppNotification.php` — kirim WA via wacenter.unisya.ac.id, queue:high
- `app/Jobs/SendEmailNotification.php` — kirim email via SMTP dari system_settings, queue:high
- `routes/api.php` — semua endpoint /api/v1/* dengan grup middleware lengkap
- `app/Providers/AppServiceProvider.php` — rate limiter (otp-request/auth/api/export) + observer registration

**Frontend**
- `resources/js/services/api.js` — Axios instance terpusat dengan interceptor 401/403
- `resources/js/stores/auth.js` — Pinia store: loginAdmin, loginOtp, loginEmployer, logout, fetchMe
- `resources/js/layouts/AuthLayout.vue` — split panel (branding kiri + form kanan)
- `resources/js/layouts/AdminLayout.vue` — fixed sidebar + topbar + breadcrumb + avatar dropdown
- `resources/js/layouts/AlumniLayout.vue` — topbar nav, mobile hamburger
- `resources/js/layouts/EmployerLayout.vue` — minimal distraction-free layout
- `resources/js/components/sidebar/SidebarItem.vue` — router-link dengan inline SVG icon
- `resources/js/components/sidebar/SidebarGroup.vue` — collapsible group dengan chevron animasi
- `resources/js/router/index.js` — route definitions lengkap (auth/admin/alumni/employer) + navigation guards
- `resources/js/main.js` — global component registration (SidebarItem, SidebarGroup)
- `resources/js/App.vue` — root shell

**Documentation**
- `docs/08_PHASE_TRACKER.md` — update: 1A.1–1A.19 ✅, 1B.1–1B.22 ✅, Selesai: 38/199
- `docs/09_CHANGELOG.md` — tambah entri v1.2.0

---

## [1.1.0] — 2026-06-09 (Sesi 1A — Setup Proyek & Database)

### Added

**Config**
- `config/tracer.php` — OTP expiry, max attempts, login lockout, employer token expiry
- `config/cors.php` — CORS sesuai 07_SECURITY.md §10
- `config/whatsapp.php` — WA gateway config (url, api_key, sender)
- `.env.example` — template lengkap dengan WA gateway, OTP, Redis, queue
- `vite.config.js` — Vue 3 plugin + path alias @
- `tailwind.config.js` — design tokens UNISYA (warna, font, radius)

**Database Migrations** (urutan eksekusi)
- `xxxx_create_users_table.php` — role ENUM, login_attempts, locked_until, SoftDeletes
- `xxxx_create_personal_access_tokens_table.php` — Sanctum standard
- `xxxx_create_otp_codes_table.php` — **code VARCHAR(64) SHA-256**
- `xxxx_create_audit_logs_table.php` — append-only, index: user_id/action/module/created_at
- `xxxx_create_faculties_table.php`
- `xxxx_create_study_programs_table.php` — FK faculties
- `xxxx_create_graduation_years_table.php`
- `xxxx_create_system_settings_table.php`
- `xxxx_create_industry_sectors_table.php`
- `xxxx_create_salary_ranges_table.php`

**Models**
- `app/Models/User.php` — SoftDeletes, isLocked(), incrementLoginAttempts(), hasOne Alumni/Employer
- `app/Models/OtpCode.php` — scopeActive(), belongsTo User nullable
- `app/Models/AuditLog.php` — append-only (no SoftDeletes), static AuditLog::record()
- `app/Models/Faculty.php`, `StudyProgram.php`, `GraduationYear.php`
- `app/Models/SystemSetting.php`, `IndustrySector.php`, `SalaryRange.php`

**Seeders**
- `database/seeders/SuperadminSeeder.php` — bcrypt cost 12
- `database/seeders/FacultySeeder.php` — 4 fakultas UNISYA
- `database/seeders/StudyProgramSeeder.php` — 10 prodi
- `database/seeders/GraduationYearSeeder.php` — angkatan 2018–2024
- `database/seeders/IndustrySectorSeeder.php` — 15 sektor industri
- `database/seeders/SalaryRangeSeeder.php` — 6 range gaji
- `database/seeders/SystemSettingSeeder.php` — WA gateway keys + SMTP + university info
- `database/seeders/DatabaseSeeder.php` — orkestrasi urutan seeder

**Observers (placeholder)**
- `app/Observers/AlumniObserver.php`
- `app/Observers/EmployerObserver.php`
- `app/Observers/SurveyResponseObserver.php`
- `app/Observers/UserObserver.php`

---

## [1.0.0] — 2026-06-09 (Initial Setup)

### Added
- Laravel 12 project scaffold
- Blueprint dokumen: 01–09 (v1.0.3)
- GitHub repository: elmansurnet/sitras-unisya
