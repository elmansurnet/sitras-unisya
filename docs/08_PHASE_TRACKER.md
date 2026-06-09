# 08 PHASE TRACKER — SITRAS UNISYA

> **Selesai: 38/199 task**
> **Fase Aktif: Fase 1B — Sesi 1B (Auth Backend + Frontend Core)**
> **Terakhir diupdate: 2026-06-09**

---

## RINGKASAN FASE

| Fase | Nama | Status | Selesai |
|------|------|--------|---------|
| 1A | Setup Proyek & Database | ✅ Selesai | 19/19 |
| 1B | Auth Backend + Frontend Core | ✅ Selesai | 19/19 |
| 2A | Alumni Module Backend | ⏳ Pending | 0/20 |
| 2B | Alumni Module Frontend | ⏳ Pending | 0/18 |
| 3A | Employer Module | ⏳ Pending | 0/16 |
| 3B | Survey & Questionnaire | ⏳ Pending | 0/22 |
| 4A | Invitation & Notification | ⏳ Pending | 0/18 |
| 4B | Reports & Analytics | ⏳ Pending | 0/20 |
| 5A | Admin System Module | ⏳ Pending | 0/18 |
| 5B | Polish, Testing & Deployment | ⏳ Pending | 0/29 |

---

## FASE 1A — Setup Proyek & Database

### Sesi 1A (2026-06-09)

| # | Task | Status |
|---|------|--------|
| 1A.1  | Install Laravel 12 + konfigurasi awal | ✅ |
| 1A.2  | Install & konfigurasi frontend stack (Vue 3 + Vite + Tailwind) | ✅ |
| 1A.3  | Konfigurasi .env.example | ✅ |
| 1A.4  | Setup Redis (cache, session, queue) | ✅ |
| 1A.5  | Buat config/tracer.php | ✅ |
| 1A.6  | Migration: users + personal_access_tokens | ✅ |
| 1A.7  | Migration: otp_codes (code=VARCHAR(64) SHA-256) | ✅ |
| 1A.8  | Migration: audit_logs | ✅ |
| 1A.9  | Migration: faculties + study_programs + graduation_years | ✅ |
| 1A.10 | Migration: system_settings + industry_sectors + salary_ranges | ✅ |
| 1A.11 | Model: User | ✅ |
| 1A.12 | Model: OtpCode | ✅ |
| 1A.13 | Model: AuditLog (append-only, AuditLog::record()) | ✅ |
| 1A.14 | Model: Faculty, StudyProgram, GraduationYear, SystemSetting, IndustrySector, SalaryRange | ✅ |
| 1A.15 | Seeder: SuperadminSeeder | ✅ |
| 1A.16 | Seeder: FacultySeeder, StudyProgramSeeder, GraduationYearSeeder | ✅ |
| 1A.17 | Seeder: IndustrySectorSeeder, SalaryRangeSeeder, SystemSettingSeeder | ✅ |
| 1A.18 | Konfigurasi config/cors.php | ✅ |
| 1A.19 | Registrasi Observers di AppServiceProvider (placeholder) | ✅ |

---

## FASE 1B — Auth Backend + Frontend Core

### Sesi 1B (2026-06-09)

| # | Task | Status |
|---|------|--------|
| 1B.1  | router/index.js + main.js + vite.config.js + App.vue | ✅ |
| 1B.2  | Middleware: CheckRole | ✅ |
| 1B.3  | Middleware: EnsureAccountActive | ✅ |
| 1B.4  | Middleware: ValidateEmployerToken | ✅ |
| 1B.5  | Middleware: LogActivity | ✅ |
| 1B.6  | Middleware: SecurityHeaders | ✅ |
| 1B.7  | Controller: AuthController (loginAdmin, logout, me) | ✅ |
| 1B.8  | Controller: OtpController (request, verify) | ✅ |
| 1B.9  | Controller: EmployerAuthController (tokenAccess) | ✅ |
| 1B.10 | FormRequest: LoginRequest, OtpRequestRequest, OtpVerifyRequest | ✅ |
| 1B.11 | Job: SendWhatsAppNotification | ✅ |
| 1B.12 | Job: SendEmailNotification | ✅ |
| 1B.13 | routes/api.php (lengkap dengan semua grup middleware) | ✅ |
| 1B.14 | AppServiceProvider (rate limiter + observer registration) | ✅ |
| 1B.15 | services/api.js (Axios instance terpusat) | ✅ |
| 1B.16 | stores/auth.js (Pinia auth store) | ✅ |
| 1B.17 | layouts/AuthLayout.vue | ✅ |
| 1B.18 | layouts/AdminLayout.vue | ✅ |
| 1B.19 | layouts/AlumniLayout.vue + EmployerLayout.vue | ✅ |
| 1B.20 | components/sidebar/SidebarItem.vue + SidebarGroup.vue | ✅ |
| 1B.21 | router/index.js (route definitions lengkap + navigation guards) | ✅ |
| 1B.22 | main.js (global component registration) + App.vue | ✅ |

---

## FASE 2A — Alumni Module Backend ⏳

### Sesi 2A (Pending)

| # | Task | Status |
|---|------|--------|
| 2A.1  | Migration: alumni + employment_histories | ⏳ |
| 2A.2  | Migration: alumni_documents | ⏳ |
| 2A.3  | Model: Alumni ($fillable, $casts, relationships, gpa→decimal:2) | ⏳ |
| 2A.4  | Model: EmploymentHistory | ⏳ |
| 2A.5  | Model: AlumniDocument | ⏳ |
| 2A.6  | Observer: AlumniObserver (implementasi penuh) | ⏳ |
| 2A.7  | Controller: Admin\AlumniController (index, show, update, destroy) | ⏳ |
| 2A.8  | Controller: Admin\AlumniImportController (store) | ⏳ |
| 2A.9  | Controller: Alumni\ProfileController (show, update) | ⏳ |
| 2A.10 | Controller: Alumni\EmploymentController (index, store, update, destroy) | ⏳ |
| 2A.11 | Controller: Alumni\DocumentController (index, store, destroy) | ⏳ |
| 2A.12 | FormRequest: Alumni\UpdateProfileRequest | ⏳ |
| 2A.13 | FormRequest: Alumni\StoreEmploymentRequest | ⏳ |
| 2A.14 | Service: AlumniImportService (Excel parsing, validasi, batch insert) | ⏳ |
| 2A.15 | Resource: AlumniResource, AlumniDetailResource | ⏳ |
| 2A.16 | Resource: EmploymentHistoryResource | ⏳ |
| 2A.17 | Policy: AlumniPolicy | ⏳ |
| 2A.18 | Job: ProcessAlumniImport (queue job) | ⏳ |
| 2A.19 | Seeder: AlumniSeeder (data dummy untuk testing) | ⏳ |
| 2A.20 | Update routes/api.php: alumni routes penuh | ⏳ |

---

## FASE 2B — Alumni Module Frontend ⏳

### Sesi 2B (Pending)

| # | Task | Status |
|---|------|--------|
| 2B.1  | store: alumni.js (Pinia) | ⏳ |
| 2B.2  | views/admin/alumni/IndexPage.vue | ⏳ |
| 2B.3  | views/admin/alumni/DetailPage.vue | ⏳ |
| 2B.4  | views/admin/alumni/ImportPage.vue | ⏳ |
| 2B.5  | views/alumni/HomePage.vue | ⏳ |
| 2B.6  | views/alumni/ProfilePage.vue | ⏳ |
| 2B.7  | views/alumni/EmploymentPage.vue | ⏳ |
| 2B.8  | components/alumni/AlumniTable.vue | ⏳ |
| 2B.9  | components/alumni/AlumniFilter.vue | ⏳ |
| 2B.10 | components/alumni/EmploymentForm.vue | ⏳ |
| 2B.11 | components/alumni/ImportDropzone.vue | ⏳ |
| 2B.12 | components/common/DataTable.vue | ⏳ |
| 2B.13 | components/common/Pagination.vue | ⏳ |
| 2B.14 | components/common/Modal.vue | ⏳ |
| 2B.15 | components/common/Toast.vue + useToast.js composable | ⏳ |
| 2B.16 | components/common/ConfirmDialog.vue | ⏳ |
| 2B.17 | components/common/LoadingSpinner.vue | ⏳ |
| 2B.18 | views/auth/LoginPage.vue + OtpRequestPage.vue + OtpVerifyPage.vue | ⏳ |

---

## FASE 3A — Employer Module ⏳

| # | Task | Status |
|---|------|--------|
| 3A.1  | Migration: employers | ⏳ |
| 3A.2  | Model: Employer | ⏳ |
| 3A.3  | Observer: EmployerObserver (implementasi penuh) | ⏳ |
| 3A.4  | Controller: Admin\EmployerController | ⏳ |
| 3A.5  | Service: EmployerTokenService (generate, validate, revoke) | ⏳ |
| 3A.6  | Resource: EmployerResource | ⏳ |
| 3A.7  | views/admin/employer/IndexPage.vue | ⏳ |
| 3A.8  | views/admin/employer/DetailPage.vue | ⏳ |
| 3A.9  | store: employer.js | ⏳ |
| 3A.10 | views/auth/EmployerAccessPage.vue | ⏳ |
| 3A.11 | views/employer/SurveyFillPage.vue | ⏳ |
| 3A.12 | views/employer/SurveyDonePage.vue | ⏳ |
| 3A.13 | components/employer/EmployerForm.vue | ⏳ |
| 3A.14 | Seeder: EmployerSeeder | ⏳ |
| 3A.15 | Policy: EmployerPolicy | ⏳ |
| 3A.16 | Update routes/api.php: employer routes penuh | ⏳ |

---

## FASE 3B — Survey & Questionnaire ⏳

| # | Task | Status |
|---|------|--------|
| 3B.1  | Migration: questionnaires + questions + question_options | ⏳ |
| 3B.2  | Migration: survey_periods | ⏳ |
| 3B.3  | Migration: survey_invitations | ⏳ |
| 3B.4  | Migration: survey_responses + response_answers | ⏳ |
| 3B.5  | Model: Questionnaire + Question + QuestionOption | ⏳ |
| 3B.6  | Model: SurveyPeriod + SurveyInvitation | ⏳ |
| 3B.7  | Model: SurveyResponse + ResponseAnswer | ⏳ |
| 3B.8  | Observer: SurveyResponseObserver (implementasi penuh) | ⏳ |
| 3B.9  | Controller: Admin\QuestionnaireController (CRUD + builder) | ⏳ |
| 3B.10 | Controller: Admin\SurveyPeriodController | ⏳ |
| 3B.11 | Controller: Admin\SurveyInvitationController (bulk send) | ⏳ |
| 3B.12 | Controller: Alumni\SurveyController (list, fill, submit) | ⏳ |
| 3B.13 | Controller: Employer\SurveyController (fill, submit) | ⏳ |
| 3B.14 | Service: SurveyInvitationService | ⏳ |
| 3B.15 | Service: SurveyResponseService | ⏳ |
| 3B.16 | Resource: QuestionnaireResource + QuestionResource | ⏳ |
| 3B.17 | Resource: SurveyResponseResource | ⏳ |
| 3B.18 | views/admin/survey/PeriodsPage.vue | ⏳ |
| 3B.19 | views/admin/survey/QuestionnairesPage.vue | ⏳ |
| 3B.20 | views/admin/survey/QuestionnaireEditPage.vue (drag-drop builder) | ⏳ |
| 3B.21 | views/admin/survey/InvitationsPage.vue | ⏳ |
| 3B.22 | views/alumni/SurveyPage.vue + SurveyFillPage.vue | ⏳ |

---

## FASE 4A — Invitation & Notification ⏳

| # | Task | Status |
|---|------|--------|
| 4A.1  | Migration: notification_logs | ⏳ |
| 4A.2  | Model: NotificationLog | ⏳ |
| 4A.3  | Service: WhatsAppService (kirim via wacenter.unisya.ac.id) | ⏳ |
| 4A.4  | Service: EmailService | ⏳ |
| 4A.5  | Service: NotificationService (facade WA + Email) | ⏳ |
| 4A.6  | Controller: Admin\NotificationController | ⏳ |
| 4A.7  | Controller: Admin\BulkInvitationController | ⏳ |
| 4A.8  | Job: BulkInvitationJob (chunked queue) | ⏳ |
| 4A.9  | Event: InvitationSent + Listener | ⏳ |
| 4A.10 | views/admin/NotificationsPage.vue | ⏳ |
| 4A.11 | store: notification.js | ⏳ |
| 4A.12 | components/notification/NotificationLog.vue | ⏳ |
| 4A.13 | components/notification/BulkSendForm.vue | ⏳ |
| 4A.14 | Template WA: OTP, undangan alumni, undangan employer | ⏳ |
| 4A.15 | Template Email: OTP, undangan alumni, undangan employer | ⏳ |
| 4A.16 | Seeder: NotificationTemplateSeeder (isi system_settings) | ⏳ |
| 4A.17 | Rate limiting: throttle notifikasi massal | ⏳ |
| 4A.18 | Test: NotificationServiceTest | ⏳ |

---

## FASE 4B — Reports & Analytics ⏳

| # | Task | Status |
|---|------|--------|
| 4B.1  | Controller: Admin\ReportController (statistik, filter, export) | ⏳ |
| 4B.2  | Service: ReportService (agregasi data) | ⏳ |
| 4B.3  | Resource: ReportResource (format chart-ready) | ⏳ |
| 4B.4  | Export: AlumniExport (Excel + DomPDF) | ⏳ |
| 4B.5  | Export: SurveyResponseExport | ⏳ |
| 4B.6  | Export: ReportExport (PDF ringkasan) | ⏳ |
| 4B.7  | views/admin/ReportsPage.vue | ⏳ |
| 4B.8  | store: report.js | ⏳ |
| 4B.9  | components/report/ChartEmploymentStatus.vue (ApexCharts) | ⏳ |
| 4B.10 | components/report/ChartWaitingTime.vue | ⏳ |
| 4B.11 | components/report/ChartSectorDistribution.vue | ⏳ |
| 4B.12 | components/report/ChartSalaryRange.vue | ⏳ |
| 4B.13 | components/report/ChartGeoMap.vue (Leaflet.js) | ⏳ |
| 4B.14 | components/report/FilterPanel.vue | ⏳ |
| 4B.15 | components/report/ExportButton.vue | ⏳ |
| 4B.16 | API: GET /admin/reports/summary | ⏳ |
| 4B.17 | API: GET /admin/reports/charts/* | ⏳ |
| 4B.18 | API: GET /admin/reports/export | ⏳ |
| 4B.19 | Controller: Public\StatisticsController (data publik) | ⏳ |
| 4B.20 | Cache: report cache dengan Redis (TTL 1 jam) | ⏳ |

---

## FASE 5A — Admin System Module ⏳

| # | Task | Status |
|---|------|--------|
| 5A.1  | Controller: Admin\UserController (CRUD admin) | ⏳ |
| 5A.2  | Controller: Admin\SettingController (baca/tulis system_settings) | ⏳ |
| 5A.3  | Controller: Admin\AuditLogController (index, filter) | ⏳ |
| 5A.4  | Resource: UserResource, AuditLogResource | ⏳ |
| 5A.5  | views/admin/system/UsersPage.vue | ⏳ |
| 5A.6  | views/admin/system/SettingsPage.vue (WA config, SMTP, dsb) | ⏳ |
| 5A.7  | views/admin/system/AuditLogPage.vue | ⏳ |
| 5A.8  | store: system.js | ⏳ |
| 5A.9  | components/system/UserForm.vue | ⏳ |
| 5A.10 | components/system/SettingForm.vue | ⏳ |
| 5A.11 | components/system/AuditLogTable.vue | ⏳ |
| 5A.12 | Observer: UserObserver (implementasi penuh) | ⏳ |
| 5A.13 | Policy: UserPolicy | ⏳ |
| 5A.14 | FormRequest: StoreUserRequest, UpdateUserRequest | ⏳ |
| 5A.15 | FormRequest: UpdateSettingRequest | ⏳ |
| 5A.16 | Test: SettingControllerTest | ⏳ |
| 5A.17 | Test: AuditLogTest | ⏳ |
| 5A.18 | Artisan Command: sitras:test-wa (test WA gateway) | ⏳ |

---

## FASE 5B — Polish, Testing & Deployment ⏳

| # | Task | Status |
|---|------|--------|
| 5B.1  | views/errors/UnauthorizedPage.vue (403) | ⏳ |
| 5B.2  | views/errors/NotFoundPage.vue (404) | ⏳ |
| 5B.3  | components/common/EmptyState.vue | ⏳ |
| 5B.4  | components/common/SkeletonLoader.vue | ⏳ |
| 5B.5  | components/common/Badge.vue | ⏳ |
| 5B.6  | components/common/Alert.vue | ⏳ |
| 5B.7  | Feature: Dark mode toggle | ⏳ |
| 5B.8  | Feature: Multi-language (id/en) | ⏳ |
| 5B.9  | Test: AuthControllerTest (login, OTP, employer token) | ⏳ |
| 5B.10 | Test: AlumniControllerTest | ⏳ |
| 5B.11 | Test: SurveyResponseTest | ⏳ |
| 5B.12 | Test: RateLimiterTest | ⏳ |
| 5B.13 | Test: SecurityHeadersTest | ⏳ |
| 5B.14 | Test: OtpServiceTest | ⏳ |
| 5B.15 | Test: AuditLogTest | ⏳ |
| 5B.16 | Test: ExportTest | ⏳ |
| 5B.17 | Test: JobsTest (WA + Email) | ⏳ |
| 5B.18 | Config: Nginx (production config) | ⏳ |
| 5B.19 | Config: supervisor.conf (queue worker) | ⏳ |
| 5B.20 | Config: .env.production template | ⏳ |
| 5B.21 | Script: deploy.sh (zero-downtime) | ⏳ |
| 5B.22 | Script: backup.sh (DB + storage) | ⏳ |
| 5B.23 | Dokumentasi: API Postman Collection | ⏳ |
| 5B.24 | Dokumentasi: README.md lengkap | ⏳ |
| 5B.25 | Dokumentasi: Developer Guide | ⏳ |
| 5B.26 | Performance: query optimization + eager loading audit | ⏳ |
| 5B.27 | Performance: Redis cache untuk endpoint berat | ⏳ |
| 5B.28 | Security: final OWASP checklist | ⏳ |
| 5B.29 | QA: end-to-end test manual (semua user role) | ⏳ |

---

## Known Constraints

- WA Gateway UNISYA: **no webhook, no delivered status** — `delivered_at` selalu NULL
- `survey_periods` tidak punya FK ke `questionnaires` (disengaja, fleksibilitas)
- OTP: hash SHA-256 → VARCHAR(64)
- File upload: `storage/app/private/` (bukan public)
- `gpa` harus `number` (bukan string) di API response

## Technical Decisions Made

| Keputusan | Alasan |
|-----------|--------|
| Sanctum token-based (bukan cookie) | Memudahkan mobile app di masa depan |
| SHA-256 untuk OTP | Sesuai 07_SECURITY.md §2 (A02) |
| Queue 3 priority: high/default/low | Notifikasi di high, import di default |
| Rate limiter via AppServiceProvider | Fleksibel, tidak perlu file config terpisah |
| SidebarItem inline SVG | Tidak perlu library icon, mengurangi bundle size |
| router/index.js lazy-load semua views | Code splitting otomatis per route |
