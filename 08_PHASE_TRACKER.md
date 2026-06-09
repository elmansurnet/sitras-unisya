# 08_PHASE_TRACKER.md
# PHASE TRACKER — SITRAS UNISYA
# Versi: 1.3.0 | Terakhir diperbarui: 2026-06-09

---

## RINGKASAN PROGRES

| Metrik | Nilai |
|--------|-------|
| **Task Selesai** | **31 / 199** |
| **Fase Aktif** | Fase 2 — Sesi 2A |
| **Sesi Selesai** | 1A, 1B, 1C, 2A (Batch A + Batch B) |
| **Sesi Berikutnya** | 2B — Manajemen Employer |

---

## FASE 1 — FONDASI & AUTENTIKASI ✅ SELESAI

### Sesi 1A — Setup Proyek & Database ✅
| # | Task | Status |
|---|------|--------|
| 1A.1 | Install Laravel 12 + konfigurasi awal | ✅ |
| 1A.2 | Install & konfigurasi frontend stack (Vue 3, Vite, Tailwind) | ✅ |
| 1A.3 | Konfigurasi .env.example | ✅ |
| 1A.4 | Setup Redis (cache, session, queue) | ✅ |
| 1A.5 | Buat config/tracer.php | ✅ |
| 1A.6 | Migration: users + personal_access_tokens | ✅ |
| 1A.7 | Migration: otp_codes (code=VARCHAR(64) SHA-256) | ✅ |
| 1A.8 | Migration: audit_logs | ✅ |
| 1A.9 | Migration: faculties + study_programs + graduation_years | ✅ |
| 1A.10 | Migration: system_settings + industry_sectors + salary_ranges | ✅ |
| 1A.11 | Model: User | ✅ |
| 1A.12 | Model: OtpCode | ✅ |
| 1A.13 | Model: AuditLog | ✅ |
| 1A.14 | Model: Faculty, StudyProgram, GraduationYear, SystemSetting, IndustrySector, SalaryRange | ✅ |
| 1A.15 | Seeder: SuperadminSeeder | ✅ |
| 1A.16 | Seeder: FacultySeeder, StudyProgramSeeder, GraduationYearSeeder | ✅ |
| 1A.17 | Seeder: IndustrySectorSeeder, SalaryRangeSeeder, SystemSettingSeeder | ✅ |
| 1A.18 | Konfigurasi config/cors.php | ✅ |
| 1A.19 | Registrasi Observers di AppServiceProvider | ✅ |

### Sesi 1B — Autentikasi Backend ✅
| # | Task | Status |
|---|------|--------|
| 1B.1 | Install Laravel Sanctum | ✅ |
| 1B.2 | OtpService | ✅ |
| 1B.3 | AuthService | ✅ |
| 1B.4 | OtpController (request + verify) | ✅ |
| 1B.5 | AuthController (login + logout + me + employer token) | ✅ |
| 1B.6 | Middleware: EnsureAccountActive, CheckRole, LogActivity, ValidateEmployerToken | ✅ |
| 1B.7 | Routes autentikasi di routes/api.php | ✅ |

### Sesi 1C — Frontend Autentikasi ✅
| # | Task | Status |
|---|------|--------|
| 1C.1 | Setup Vue Router 4 + layout shell | ✅ |
| 1C.2 | Pinia: authStore | ✅ |
| 1C.3 | Halaman Login (OTP + email/password) | ✅ |
| 1C.4 | Axios interceptor + token management | ✅ |
| 1C.5 | Route guard (auth + role-based) | ✅ |

---

## FASE 2 — MANAJEMEN DATA MASTER 🔄 SEDANG BERJALAN

### Sesi 2A — Manajemen Alumni ✅ BATCH A + BATCH B SELESAI
| # | Task | Status | File |
|---|------|--------|------|
| 2A.1 | Migration: alumni | ✅ | `database/migrations/*_create_alumni_table.php` |
| 2A.2 | Migration: alumni_work_histories | ✅ | `database/migrations/*_create_alumni_work_histories_table.php` |
| 2A.3 | Migration: survey_responses (skeleton) | ✅ | `database/migrations/*_create_survey_responses_table.php` |
| 2A.4 | Model: Alumni, AlumniWorkHistory | ✅ | `app/Models/Alumni.php`, `app/Models/AlumniWorkHistory.php` |
| 2A.5 | AlumniRepository | ✅ | `app/Repositories/AlumniRepository.php` |
| 2A.6 | AlumniService | ✅ | `app/Services/AlumniService.php` |
| 2A.7 | ImportExportService | ✅ | `app/Services/ImportExportService.php` |
| 2A.8 | AlumniPolicy (07_SECURITY.md §3.3) | ✅ | `app/Policies/AlumniPolicy.php` |
| 2A.9 | FormRequests: Store/Update/Import/SendInvitation | ✅ | `app/Http/Requests/Alumni/*.php` |
| 2A.10 | Admin\AlumniController (CRUD + import + export + stats + invite) | ✅ | `app/Http/Controllers/Api/V1/Admin/AlumniController.php` |
| 2A.11 | Alumni\ProfileController (show + update + uploadPhoto) | ✅ | `app/Http/Controllers/Api/V1/Alumni/ProfileController.php` |
| 2A.12 | Alumni\WorkHistoryController (CRUD self + indexForAdmin) | ✅ | `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` |
| 2A.13 | Routes alumni admin + alumni self di routes/api.php | ✅ | `routes/api.php` |
| 2A.14 | Job: SendBulkInvitationJob, GenerateReportExport, AlumniExport | ✅ | `app/Jobs/*.php`, `app/Exports/AlumniExport.php` |

### Sesi 2B — Manajemen Employer ⏳
| # | Task | Status |
|---|------|--------|
| 2B.1 | Migration: employers | ⏳ |
| 2B.2 | Migration: employer_alumni_relations | ⏳ |
| 2B.3 | Model: Employer | ⏳ |
| 2B.4 | EmployerRepository | ⏳ |
| 2B.5 | EmployerService (CRUD + token generation + send survey) | ⏳ |
| 2B.6 | EmployerPolicy | ⏳ |
| 2B.7 | FormRequests: Store/Update Employer | ⏳ |
| 2B.8 | Admin\EmployerController | ⏳ |
| 2B.9 | Employer\SurveyController (akses via token) | ⏳ |
| 2B.10 | Routes employer di routes/api.php | ⏳ |
| 2B.11 | Job: SendEmployerSurveyTokenJob | ⏳ |

### Sesi 2C — Frontend Manajemen Alumni & Employer ⏳
| # | Task | Status |
|---|------|--------|
| 2C.1 | Pinia: alumniStore (CRUD + import/export + invite) | ⏳ |
| 2C.2 | Halaman Admin: Daftar Alumni (tabel + filter + search) | ⏳ |
| 2C.3 | Halaman Admin: Detail Alumni | ⏳ |
| 2C.4 | Halaman Admin: Form Tambah/Edit Alumni | ⏳ |
| 2C.5 | Modal Import Alumni + drag-drop upload | ⏳ |
| 2C.6 | Pinia: employerStore | ⏳ |
| 2C.7 | Halaman Admin: Daftar & Detail Employer | ⏳ |
| 2C.8 | Halaman Alumni: Profil Self-Service | ⏳ |
| 2C.9 | Halaman Alumni: Riwayat Pekerjaan | ⏳ |

---

## FASE 3 — KUESIONER & PERIODE SURVEI ⏳

### Sesi 3A — Kuesioner Dinamis ⏳
| # | Task | Status |
|---|------|--------|
| 3A.1 | Migration: questionnaires, sections, questions, options | ⏳ |
| 3A.2 | Model: Questionnaire, QuestionnaireSection, Question, QuestionOption | ⏳ |
| 3A.3 | QuestionnaireService (CRUD + publish + archive + reorder) | ⏳ |
| 3A.4 | Admin\QuestionnaireController | ⏳ |
| 3A.5 | Routes kuesioner | ⏳ |
| 3A.6 | Frontend: Builder Kuesioner Dinamis | ⏳ |

### Sesi 3B — Periode Survei ⏳
| # | Task | Status |
|---|------|--------|
| 3B.1 | Migration: survey_periods, survey_period_graduation_years | ⏳ |
| 3B.2 | Model: SurveyPeriod | ⏳ |
| 3B.3 | SurveyPeriodService (CRUD + activate + close + bulk invite) | ⏳ |
| 3B.4 | Admin\SurveyPeriodController | ⏳ |
| 3B.5 | Routes periode survei | ⏳ |
| 3B.6 | Frontend: Manajemen Periode Survei | ⏳ |

---

## FASE 4 — PENGISIAN SURVEI ⏳

### Sesi 4A — Backend Survei Alumni ⏳
| # | Task | Status |
|---|------|--------|
| 4A.1 | SurveyResponseService (start + save draft + submit) | ⏳ |
| 4A.2 | Alumni\SurveyController | ⏳ |
| 4A.3 | Frontend: Flow pengisian survei alumni (multi-step) | ⏳ |

### Sesi 4B — Backend Survei Employer ⏳
| # | Task | Status |
|---|------|--------|
| 4B.1 | SurveyResponseService employer variant | ⏳ |
| 4B.2 | Employer\SurveyController | ⏳ |
| 4B.3 | Frontend: Halaman survei employer (via token) | ⏳ |

---

## FASE 5 — DASHBOARD & LAPORAN ⏳

### Sesi 5A — Dashboard & Statistik ⏳
| # | Task | Status |
|---|------|--------|
| 5A.1 | DashboardService (summary + employment-stats + geographical) | ⏳ |
| 5A.2 | Admin\DashboardController | ⏳ |
| 5A.3 | Frontend: Dashboard Admin (charts, KPIs, map) | ⏳ |

### Sesi 5B — Laporan & Export ⏳
| # | Task | Status |
|---|------|--------|
| 5B.1 | ReportService (generate PDF + Excel) | ⏳ |
| 5B.2 | Admin\ReportController | ⏳ |
| 5B.3 | Frontend: Halaman Laporan | ⏳ |

---

## FASE 6 — SYSTEM SETTINGS & FINALISASI ⏳

### Sesi 6A — System Settings ⏳
| # | Task | Status |
|---|------|--------|
| 6A.1 | Admin\SystemSettingController | ⏳ |
| 6A.2 | Frontend: Halaman Pengaturan Sistem | ⏳ |

### Sesi 6B — Audit Log & Security Hardening ⏳
| # | Task | Status |
|---|------|--------|
| 6B.1 | Admin\AuditLogController | ⏳ |
| 6B.2 | Frontend: Halaman Audit Log | ⏳ |
| 6B.3 | Security review & OWASP checklist | ⏳ |

### Sesi 6C — Testing & Deployment ⏳
| # | Task | Status |
|---|------|--------|
| 6C.1 | Unit tests: Services & Repositories | ⏳ |
| 6C.2 | Feature tests: API endpoints | ⏳ |
| 6C.3 | Konfigurasi Nginx + supervisor + deployment script | ⏳ |

---

## TECHNICAL DECISIONS LOG

| Keputusan | Alasan |
|-----------|--------|
| OTP: SHA-256 → VARCHAR(64) | 07_SECURITY.md §2 — A02 Cryptographic Failures |
| survey_periods tidak FK ke questionnaires | Fleksibilitas: 1 periode bisa gunakan kuesioner berbeda per batch |
| File upload → storage/app/private/ | 07_SECURITY.md §5 — file tidak boleh public |
| gpa → number (bukan string) di API | 05_API.md §1.2 — semua angka pecahan adalah number |
| queue: high/default/low | Prioritas: high=notifikasi, default=export, low=cleanup |
| AlumniPolicy: delete → superadmin only | 07_SECURITY.md §3.3 |
| WorkHistory is_current reset on new current | Business logic: hanya 1 pekerjaan aktif |
| AlumniController akses repo via service.alumniRepo | Repository public property di service untuk consistency |

---

## KNOWN CONSTRAINTS

- WA Gateway UNISYA: no webhook, no delivered status otomatis
- survey_periods TIDAK punya FK ke questionnaires (by design)
- OTP: hash SHA-256 → VARCHAR(64)
- File upload: storage/private (bukan public)
- gpa harus number (bukan string) di API response
- Token employer: Str::random(64) plaintext, expiry 30 hari
